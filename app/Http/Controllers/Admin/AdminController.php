<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::count();
        $recentRegistrations = User::where('created_at', '>=', now()->subDays(7))->count();
        $recentLogins = AuditLog::where('action', 'login')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();

        return view('admin.dashboard', compact(
            'totalUsers',
            'recentRegistrations',
            'recentLogins',
            'verifiedUsers',
        ));
    }

    public function users(Request $request)
    {
        $query = User::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('admin.users', compact('users', 'search'));
    }

    public function userDetail($id)
    {
        $user = User::findOrFail($id);
        $auditLogs = AuditLog::where('user_id', $id)->orderByDesc('created_at')->limit(50)->get();

        $oauthTokens = DB::table('oauth_access_tokens')
            ->where('oauth_access_tokens.user_id', $id)
            ->where('oauth_access_tokens.revoked', false)
            ->join('oauth_clients', 'oauth_access_tokens.client_id', '=', 'oauth_clients.id')
            ->select('oauth_clients.name as client_name', 'oauth_access_tokens.created_at', 'oauth_access_tokens.expires_at')
            ->orderByDesc('oauth_access_tokens.created_at')
            ->get();

        return view('admin.user-detail', compact('user', 'auditLogs', 'oauthTokens'));
    }

    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|min:4|max:20|alpha_num|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:4|confirmed',
            'is_admin' => 'nullable|boolean',
            'mark_verified' => 'nullable|boolean',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => !empty($validated['is_admin']),
            'email_verified_at' => !empty($validated['mark_verified']) ? now() : null,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'admin_create_user',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['created_by' => auth()->id()],
        ]);

        return redirect('/admin/users')->with('success', "User \"{$user->username}\" created.");
    }

    public function setPassword(Request $request, $id)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ]);

        $user = User::findOrFail($id);
        $user->update(['password' => Hash::make($validated['password'])]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'admin_set_password',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['set_by' => auth()->id()],
        ]);

        return back()->with('success', "Password updated for {$user->username}.");
    }

    public function toggleAdmin(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own admin status.');
        }

        $user->update(['is_admin' => !$user->is_admin]);

        return back()->with('success', "Admin status toggled for {$user->username}.");
    }

    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('user');

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        $logs = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        $actions = AuditLog::select('action')->distinct()->pluck('action');

        return view('admin.audit-logs', compact('logs', 'actions', 'action', 'userId'));
    }

    public function oauthClients()
    {
        $clients = DB::table('oauth_clients')
            ->where('revoked', false)
            ->orderBy('name')
            ->get();

        return view('admin.oauth-clients', compact('clients'));
    }

    public function createOauthClient(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'redirect_uri' => 'required|url|max:2048',
            'is_public' => 'boolean',
            'webhook_url' => 'nullable|url|max:2048',
        ]);

        $clientRepo = app(ClientRepository::class);

        $client = $clientRepo->createAuthorizationCodeGrantClient(
            name: $validated['name'],
            redirectUris: [$validated['redirect_uri']],
            confidential: empty($validated['is_public']),
        );

        $webhookSecret = null;
        if (!empty($validated['webhook_url'])) {
            $webhookSecret = Str::random(64);
            DB::table('oauth_clients')->where('id', $client->id)->update([
                'webhook_url' => $validated['webhook_url'],
                'webhook_secret' => $webhookSecret,
            ]);
        }

        return redirect('/admin/oauth-clients')
            ->with('success', "Client \"{$client->name}\" created.")
            ->with('newClient', [
                'id' => $client->id,
                'secret' => $client->secret,
                'webhook_secret' => $webhookSecret,
            ]);
    }

    public function showOauthClient($id)
    {
        $client = DB::table('oauth_clients')->where('id', $id)->firstOrFail();

        $tokenCount = DB::table('oauth_access_tokens')
            ->where('client_id', $id)
            ->where('revoked', false)
            ->count();

        return view('admin.oauth-client-detail', compact('client', 'tokenCount'));
    }

    public function updateOauthClient(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'redirect_uri' => 'required|url|max:2048',
            'webhook_url' => 'nullable|url|max:2048',
        ]);

        DB::table('oauth_clients')->where('id', $id)->update([
            'name' => $validated['name'],
            'redirect_uris' => json_encode([$validated['redirect_uri']]),
            'webhook_url' => $validated['webhook_url'] ?: null,
        ]);

        return back()->with('success', 'Client updated.');
    }

    public function regenerateOauthSecret($id)
    {
        $client = DB::table('oauth_clients')->where('id', $id)->firstOrFail();

        $newSecret = Str::random(40);
        DB::table('oauth_clients')->where('id', $id)->update([
            'secret' => $newSecret,
        ]);

        return back()
            ->with('success', 'Client secret regenerated.')
            ->with('newSecret', $newSecret);
    }

    public function regenerateWebhookSecret($id)
    {
        $client = DB::table('oauth_clients')->where('id', $id)->firstOrFail();

        $newSecret = Str::random(64);
        DB::table('oauth_clients')->where('id', $id)->update([
            'webhook_secret' => $newSecret,
        ]);

        return back()
            ->with('success', 'Webhook secret regenerated.')
            ->with('newWebhookSecret', $newSecret);
    }

    public function revokeOauthClient($id)
    {
        // Revoke all tokens for this client
        DB::table('oauth_access_tokens')->where('client_id', $id)->update(['revoked' => true]);

        // Revoke the client itself
        DB::table('oauth_clients')->where('id', $id)->update(['revoked' => true]);

        return redirect('/admin/oauth-clients')->with('success', 'Client revoked.');
    }
}

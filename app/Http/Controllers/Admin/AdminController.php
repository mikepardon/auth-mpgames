<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->where('user_id', $id)
            ->where('revoked', false)
            ->join('oauth_clients', 'oauth_access_tokens.client_id', '=', 'oauth_clients.id')
            ->select('oauth_clients.name as client_name', 'oauth_access_tokens.created_at', 'oauth_access_tokens.expires_at')
            ->orderByDesc('oauth_access_tokens.created_at')
            ->get();

        return view('admin.user-detail', compact('user', 'auditLogs', 'oauthTokens'));
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
}

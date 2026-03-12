<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $this->storeIntendedUrl();

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $socialUser = Socialite::driver('google')->user();

        return $this->handleSocialLogin('google', 'google_id', $socialUser);
    }

    public function redirectToApple(Request $request): RedirectResponse
    {
        $this->storeIntendedUrl();

        // Save the intended redirect URL to the database keyed by a token,
        // and pass that token through Apple's OAuth state parameter.
        // Sessions and cookies are lost on Apple's cross-site POST callback,
        // but the state comes back in the POST body.
        $intended = session('sso_redirect_after', '');
        $token = bin2hex(random_bytes(20));

        if ($intended) {
            \DB::table('cache')->upsert([
                'key' => 'apple_sso_' . $token,
                'value' => serialize($intended),
                'expiration' => time() + 600,
            ], ['key']);
        }

        return Socialite::driver('apple')
            ->with(['state' => $token])
            ->redirect();
    }

    public function handleAppleCallback(Request $request): RedirectResponse
    {
        $socialUser = Socialite::driver('apple')->stateless()->user();

        // Look up the intended redirect URL from the state token
        $stateToken = $request->input('state', '');
        $intended = '';

        if ($stateToken) {
            $row = \DB::table('cache')
                ->where('key', 'apple_sso_' . $stateToken)
                ->where('expiration', '>', time())
                ->first();

            if ($row) {
                $intended = unserialize($row->value);
                \DB::table('cache')->where('key', 'apple_sso_' . $stateToken)->delete();
            }
        }

        // Handle user creation/linking
        $socialId = $socialUser->getId();
        $email = $socialUser->getEmail() ? strtolower($socialUser->getEmail()) : null;

        $user = User::where('apple_id', $socialId)->first();

        if (!$user && $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update(['apple_id' => $socialId]);
            }
        }

        if (!$user) {
            $username = $this->generateUsername($socialUser->getName() ?? 'appleuser');
            $user = User::create([
                'username' => $username,
                'email' => $email,
                'apple_id' => $socialId,
                'avatar_url' => $socialUser->getAvatar(),
                'email_verified_at' => $email ? now() : null,
            ]);
        }

        if ($email && !$user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        if ($socialUser->getAvatar() && $socialUser->getAvatar() !== $user->avatar_url) {
            $user->update(['avatar_url' => $socialUser->getAvatar()]);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'social_login',
            'metadata' => ['provider' => 'apple'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // DON'T log in here — the session from this cross-site POST won't persist.
        // Instead, generate a one-time login token and redirect to a GET endpoint
        // that performs the actual login on a normal same-site request.
        $loginToken = bin2hex(random_bytes(32));
        \DB::table('cache')->upsert([
            'key' => 'apple_login_' . $loginToken,
            'value' => serialize([
                'user_id' => $user->id,
                'redirect' => $intended,
            ]),
            'expiration' => time() + 120,
        ], ['key']);

        return redirect('/auth/apple/complete?token=' . $loginToken);
    }

    public function completeAppleAuth(Request $request): RedirectResponse
    {
        $loginToken = $request->query('token', '');

        if (!$loginToken) {
            return redirect('/login');
        }

        $row = \DB::table('cache')
            ->where('key', 'apple_login_' . $loginToken)
            ->where('expiration', '>', time())
            ->first();

        if (!$row) {
            return redirect('/login');
        }

        \DB::table('cache')->where('key', 'apple_login_' . $loginToken)->delete();

        $data = unserialize($row->value);
        $user = User::find($data['user_id']);

        if (!$user) {
            return redirect('/login');
        }

        // Log in on this normal GET request — session will persist
        Auth::login($user, true);
        session()->regenerate();

        $redirect = $data['redirect'] ?? '';
        if ($redirect) {
            $redirect = preg_replace('/([&?])provider=[^&]+(&?)/', '$1', $redirect);
            $redirect = rtrim($redirect, '&?');
            return redirect($redirect);
        }

        return redirect('/dashboard');
    }

    private function storeIntendedUrl(): void
    {
        // Preserve the intended URL (set by Passport's auth redirect) across the SSO round-trip
        $intended = session('url.intended');
        if ($intended) {
            session(['sso_redirect_after' => $intended]);
        }
    }

    private function handleSocialLogin(string $provider, string $idField, $socialUser): RedirectResponse
    {
        $socialId = $socialUser->getId();
        $email = $socialUser->getEmail() ? strtolower($socialUser->getEmail()) : null;

        // Try to find by provider ID first
        $user = User::where($idField, $socialId)->first();

        // If not found by provider ID, try by email (link accounts)
        if (!$user && $email) {
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([$idField => $socialId]);
            }
        }

        // Create new user if not found
        if (!$user) {
            $username = $this->generateUsername($socialUser->getName() ?? $provider . 'user');

            $user = User::create([
                'username' => $username,
                'email' => $email,
                $idField => $socialId,
                'avatar_url' => $socialUser->getAvatar(),
                'email_verified_at' => $email ? now() : null,
            ]);
        }

        if ($email && !$user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        if ($socialUser->getAvatar() && $socialUser->getAvatar() !== $user->avatar_url) {
            $user->update(['avatar_url' => $socialUser->getAvatar()]);
        }

        Auth::login($user, true);
        session()->regenerate();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'social_login',
            'metadata' => ['provider' => $provider],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Redirect back to the OAuth authorize flow if one was pending
        $redirectAfter = session()->pull('sso_redirect_after');
        if ($redirectAfter) {
            $redirectAfter = preg_replace('/([&?])provider=[^&]+(&?)/', '$1', $redirectAfter);
            $redirectAfter = rtrim($redirectAfter, '&?');
            return redirect($redirectAfter);
        }

        return redirect('/dashboard');
    }

    private function generateUsername(?string $name): string
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name ?? 'user'));

        if (strlen($base) < 4) {
            $base .= 'user';
        }

        $base = substr($base, 0, 16);

        $username = $base;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . $suffix;
            $suffix++;
        }

        return $username;
    }
}
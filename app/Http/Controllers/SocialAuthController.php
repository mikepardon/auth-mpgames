<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
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

        // Store the intended redirect in a SameSite=None cookie so it survives
        // Apple's cross-site POST callback (session cookies are blocked by SameSite=Lax)
        $intended = session('sso_redirect_after');
        if ($intended) {
            Cookie::queue('apple_sso_redirect', $intended, 10, '/', null, true, true, false, 'None');
        }

        return Socialite::driver('apple')->redirect();
    }

    public function handleAppleCallback(Request $request): RedirectResponse
    {
        $socialUser = Socialite::driver('apple')->stateless()->user();

        // Restore the intended redirect from cookie since the original session
        // is lost on Apple's cross-site POST (SameSite=Lax blocks session cookies)
        $cookieRedirect = $request->cookie('apple_sso_redirect');
        if ($cookieRedirect && !session()->has('sso_redirect_after')) {
            session(['sso_redirect_after' => $cookieRedirect]);
        }
        Cookie::queue(Cookie::forget('apple_sso_redirect'));

        return $this->handleSocialLogin('apple', 'apple_id', $socialUser);
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
            // Strip the provider hint to prevent redirect loops (e.g. Apple POST
            // callback loses session, Passport redirects to /login, which sees
            // provider=apple and sends user back to Apple indefinitely)
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

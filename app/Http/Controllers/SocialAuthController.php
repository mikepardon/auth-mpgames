<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OneSignalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        // Store the intended URL so we can redirect back after SSO
        if ($request->query('redirect_after')) {
            session(['sso_redirect_after' => $request->query('redirect_after')]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $socialUser = Socialite::driver('google')->user();

        return $this->handleSocialLogin('google', 'google_id', $socialUser);
    }

    public function redirectToApple(Request $request): RedirectResponse
    {
        if ($request->query('redirect_after')) {
            session(['sso_redirect_after' => $request->query('redirect_after')]);
        }

        return Socialite::driver('apple')->redirect();
    }

    public function handleAppleCallback(Request $request): RedirectResponse
    {
        $socialUser = Socialite::driver('apple')->user();

        return $this->handleSocialLogin('apple', 'apple_id', $socialUser);
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
                // Link this SSO provider to existing account
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
                'email_verified_at' => $email ? now() : null, // Auto-verify SSO emails
            ]);

            if ($email) {
                app(OneSignalService::class)->registerEmail($user);
            }
        }

        // Auto-verify email if not yet verified (SSO emails are trusted)
        if ($email && !$user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        // Update avatar if changed
        if ($socialUser->getAvatar() && $socialUser->getAvatar() !== $user->avatar_url) {
            $user->update(['avatar_url' => $socialUser->getAvatar()]);
        }

        Auth::login($user, true);
        session()->regenerate();

        // Redirect back to pending OAuth authorize flow if one exists
        $redirectAfter = session()->pull('sso_redirect_after');
        if ($redirectAfter) {
            return redirect($redirectAfter);
        }

        return redirect('/');
    }

    private function generateUsername(?string $name): string
    {
        // Strip non-alphanumeric, lowercase
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name ?? 'user'));

        // Ensure minimum length
        if (strlen($base) < 4) {
            $base .= 'user';
        }

        // Truncate to leave room for suffix
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

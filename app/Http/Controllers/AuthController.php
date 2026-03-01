<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        return view('auth.login');
    }

    public function showRegister(Request $request)
    {
        $intended = $request->query('intended', session('url.intended'));

        return view('auth.register', ['intended' => $intended]);
    }

    public function showVerify(Request $request)
    {
        return view('auth.verify', ['user_id' => $request->query('user_id')]);
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function showResetPassword(Request $request)
    {
        return view('auth.reset-password', [
            'token' => $request->query('token'),
            'email' => $request->query('email'),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'min:4', 'max:20', 'regex:/^[a-zA-Z0-9]+$/', 'unique:users,username'],
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:4|confirmed',
            'intended' => 'nullable|string',
        ], [
            'username.min' => 'Username must be at least 4 characters.',
            'username.max' => 'Username must be 20 characters or fewer.',
            'username.regex' => 'Username can only contain letters and numbers.',
        ]);

        $user = User::create([
            'username' => strtolower($validated['username']),
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
        ]);

        // Preserve intended URL through the verification flow
        if (!empty($validated['intended'])) {
            session(['url.intended' => $validated['intended']]);
        }

        $this->sendVerificationCode($user);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'register',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'requires_verification' => true,
            'user_id' => $user->id,
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'code' => 'required|string|size:6',
        ]);

        $verification = EmailVerificationCode::where('user_id', $validated['user_id'])
            ->where('code', $validated['code'])
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 422);
        }

        $user = User::findOrFail($validated['user_id']);
        $user->update(['email_verified_at' => now()]);

        EmailVerificationCode::where('user_id', $user->id)->delete();

        Auth::login($user);
        $request->session()->regenerate();

        $intended = session()->pull('url.intended', '/dashboard');

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'email_verified',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Email verified successfully.',
            'user' => $user->only(['id', 'username', 'email', 'email_verified_at', 'avatar_url', 'created_at']),
            'redirect' => $intended,
        ]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email already verified.'], 422);
        }

        $recent = EmailVerificationCode::where('user_id', $user->id)
            ->where('created_at', '>', now()->subSeconds(60))
            ->exists();

        if ($recent) {
            return response()->json(['message' => 'Please wait before requesting another code.'], 429);
        }

        $this->sendVerificationCode($user);

        return response()->json(['message' => 'Verification code sent.']);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $input = $validated['login'];
        $loginField = str_contains($input, '@') ? 'email' : 'username';
        $loginValue = strtolower($input);

        if (!Auth::attempt([$loginField => $loginValue, 'password' => $validated['password']])) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = Auth::user();

        if (!$user->email_verified_at) {
            $userId = $user->id;
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'requires_verification' => true,
                'user_id' => $userId,
            ], 403);
        }

        $request->session()->regenerate();

        $intended = session()->pull('url.intended', '/dashboard');

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'user' => $user->only(['id', 'username', 'email', 'email_verified_at', 'avatar_url', 'created_at']),
            'redirect' => $intended,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:4|confirmed',
        ]);

        $user = $request->user();

        if (!$user->password || !Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($validated['new_password'])]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'password_change',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Password updated.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', strtolower($validated['email']))->first();

        if (!$user) {
            // Don't reveal if the email exists
            return response()->json(['message' => 'If that email exists, a reset link has been sent.']);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()],
        );

        $resetUrl = url('/reset-password?token=' . $token . '&email=' . urlencode($user->email));

        $html = '<div style="font-family: -apple-system, BlinkMacSystemFont, sans-serif; max-width: 480px; margin: 0 auto; padding: 30px; background: #0f172a; color: #e2e8f0; border: 1px solid #334155; border-radius: 8px;">'
            . '<h2 style="color: #60a5fa; font-size: 1.4rem; text-align: center;">MPGames</h2>'
            . '<p style="text-align: center;">Reset your password by clicking the link below:</p>'
            . '<p style="text-align: center;"><a href="' . e($resetUrl) . '" style="display: inline-block; padding: 12px 24px; background: #3b82f6; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;">Reset Password</a></p>'
            . '<p style="text-align: center; font-size: 0.85rem; color: #94a3b8;">This link expires in 60 minutes.</p>'
            . '</div>';

        Mail::html($html, function ($message) use ($user) {
            $message->to($user->email)->subject('MPGames - Reset Your Password');
        });

        return response()->json(['message' => 'If that email exists, a reset link has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:4|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', strtolower($validated['email']))
            ->first();

        if (!$record || !Hash::check($validated['token'], $record->token)) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            return response()->json(['message' => 'Reset token has expired.'], 422);
        }

        $user = User::where('email', strtolower($validated['email']))->firstOrFail();
        $user->update(['password' => Hash::make($validated['password'])]);

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'password_reset',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Password has been reset.']);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        // Require password confirmation for users with a password
        if ($user->password) {
            $request->validate(['password' => 'required|string']);

            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Password is incorrect.'], 422);
            }
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'account_deleted',
            'metadata' => ['username' => $user->username, 'email' => $user->email],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Revoke all Passport tokens
        $user->tokens()->each(function ($token) {
            $token->revoke();
        });

        // Notify connected game clients via webhooks
        app(WebhookService::class)->notifyClients('user.deleted', [
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
        ]);

        $user->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Account deleted.', 'redirect' => '/login']);
    }

    private function sendVerificationCode(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        $html = '<div style="font-family: -apple-system, BlinkMacSystemFont, sans-serif; max-width: 480px; margin: 0 auto; padding: 30px; background: #0f172a; color: #e2e8f0; border: 1px solid #334155; border-radius: 8px;">'
            . '<h2 style="color: #60a5fa; font-size: 1.4rem; text-align: center;">MPGames</h2>'
            . '<p style="text-align: center;">Your verification code is:</p>'
            . '<p style="text-align: center; font-size: 2rem; font-weight: bold; letter-spacing: 6px; color: #60a5fa;">' . $code . '</p>'
            . '<p style="text-align: center; font-size: 0.85rem; color: #94a3b8;">This code expires in 15 minutes.</p>'
            . '</div>';

        Mail::html($html, function ($message) use ($user) {
            $message->to($user->email)->subject('MPGames - Verify Your Email');
        });
    }
}

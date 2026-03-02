@extends('layouts.auth')
@section('title', 'Login - MPGames')

@section('content')
<div x-data="loginForm()">
    <h2 class="text-xl font-semibold text-center mb-6">Sign In</h2>

    <template x-if="error">
        <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-2 rounded mb-4 text-sm" x-text="error"></div>
    </template>

    <form @submit.prevent="submit">
        <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">Username or Email</label>
            <input type="text" x-model="form.login" required autofocus
                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-slate-200 focus:outline-none focus:border-blue-500">
        </div>

        <div class="mb-6">
            <label class="block text-sm text-slate-400 mb-1">Password</label>
            <input type="password" x-model="form.password" required
                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-slate-200 focus:outline-none focus:border-blue-500">
        </div>

        <button type="submit" :disabled="loading"
            class="w-full py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded font-semibold transition">
            <span x-show="!loading">Sign In</span>
            <span x-show="loading">Signing in...</span>
        </button>
    </form>

    @if(config('services.google.client_id') || config('services.apple.client_id'))
    <div class="flex items-center my-6">
        <div class="flex-1 border-t border-slate-700"></div>
        <span class="px-3 text-sm text-slate-500">or</span>
        <div class="flex-1 border-t border-slate-700"></div>
    </div>

    <div class="space-y-3">
        @if(config('services.google.client_id'))
        <a href="/auth/google/redirect"
            class="flex items-center justify-center w-full py-2 bg-white text-gray-800 rounded font-medium hover:bg-gray-100 transition">
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Continue with Google
        </a>
        @endif

        @if(config('services.apple.client_id'))
        <a href="/auth/apple/redirect"
            class="flex items-center justify-center w-full py-2 bg-black text-white border border-slate-700 rounded font-medium hover:bg-gray-900 transition">
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
            Continue with Apple
        </a>
        @endif
    </div>
    @endif
</div>

<script>
function loginForm() {
    return {
        form: { login: '', password: '' },
        error: null,
        loading: false,
        async submit() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch('/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (res.status === 403 && data.requires_verification) {
                    window.location.href = '/verify-email?user_id=' + data.user_id;
                } else if (!res.ok) {
                    this.error = data.message || Object.values(data.errors || {}).flat()[0] || 'Login failed.';
                } else {
                    window.location.href = data.redirect || '/dashboard';
                }
            } catch (e) {
                this.error = 'Network error.';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endsection

@section('footer')
    Don't have an account? <a href="/register{{ session('url.intended') ? '?intended=' . urlencode(session('url.intended')) : '' }}" class="text-blue-400 hover:underline">Sign up</a>
    <span class="mx-2">|</span>
    <a href="/forgot-password" class="text-blue-400 hover:underline">Forgot password?</a>
@endsection

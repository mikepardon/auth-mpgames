@extends('layouts.auth')
@section('title', 'Verify Email - MPGames')

@section('content')
<div x-data="verifyForm()">
    <h2 class="text-xl font-semibold text-center mb-2">Verify Your Email</h2>
    <p class="text-slate-400 text-sm text-center mb-6">Enter the 6-digit code sent to your email</p>

    <template x-if="error">
        <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-2 rounded mb-4 text-sm" x-text="error"></div>
    </template>

    <template x-if="success">
        <div class="bg-green-900/50 border border-green-700 text-green-300 px-4 py-2 rounded mb-4 text-sm" x-text="success"></div>
    </template>

    <form @submit.prevent="submit">
        <div class="mb-6">
            <label class="block text-sm text-slate-400 mb-1">Verification Code</label>
            <input type="text" x-model="form.code" required maxlength="6" pattern="[0-9]{6}" placeholder="000000" autofocus
                class="w-full px-3 py-3 bg-slate-800 border border-slate-700 rounded text-slate-200 text-center text-2xl tracking-widest focus:outline-none focus:border-blue-500">
        </div>

        <button type="submit" :disabled="loading"
            class="w-full py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded font-semibold transition">
            <span x-show="!loading">Verify</span>
            <span x-show="loading">Verifying...</span>
        </button>
    </form>

    <div class="mt-4 text-center">
        <button @click="resend" :disabled="resendLoading || resendCooldown > 0"
            class="text-sm text-blue-400 hover:underline disabled:opacity-50 disabled:no-underline">
            <span x-show="resendCooldown > 0">Resend code in <span x-text="resendCooldown"></span>s</span>
            <span x-show="resendCooldown === 0 && !resendLoading">Resend code</span>
            <span x-show="resendLoading">Sending...</span>
        </button>
    </div>
</div>

<script>
function verifyForm() {
    return {
        form: { user_id: '{{ $user_id ?? '' }}', code: '' },
        error: null,
        success: null,
        loading: false,
        resendLoading: false,
        resendCooldown: 0,
        async submit() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch('/verify-email', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || 'Verification failed.';
                } else {
                    window.location.href = '/dashboard';
                }
            } catch (e) {
                this.error = 'Network error.';
            } finally {
                this.loading = false;
            }
        },
        async resend() {
            this.resendLoading = true;
            this.error = null;
            this.success = null;
            try {
                const res = await fetch('/resend-verification', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ user_id: parseInt(this.form.user_id) })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message;
                } else {
                    this.success = 'Code sent!';
                    this.resendCooldown = 60;
                    const interval = setInterval(() => {
                        this.resendCooldown--;
                        if (this.resendCooldown <= 0) clearInterval(interval);
                    }, 1000);
                }
            } catch (e) {
                this.error = 'Network error.';
            } finally {
                this.resendLoading = false;
            }
        }
    };
}
</script>
@endsection

@section('footer')
    <a href="/login" class="text-blue-400 hover:underline">Back to login</a>
@endsection

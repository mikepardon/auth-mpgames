@extends('layouts.auth')
@section('title', 'Reset Password - MPGames')

@section('content')
<div x-data="resetForm()">
    <h2 class="text-xl font-semibold text-center mb-6">Reset Password</h2>

    <template x-if="error">
        <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-2 rounded mb-4 text-sm" x-text="error"></div>
    </template>

    <template x-if="success">
        <div class="bg-green-900/50 border border-green-700 text-green-300 px-4 py-2 rounded mb-4 text-sm" x-text="success"></div>
    </template>

    <form @submit.prevent="submit">
        <div class="mb-4">
            <label class="block text-sm text-slate-400 mb-1">New Password</label>
            <input type="password" x-model="form.password" required minlength="4"
                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-slate-200 focus:outline-none focus:border-blue-500">
        </div>

        <div class="mb-6">
            <label class="block text-sm text-slate-400 mb-1">Confirm New Password</label>
            <input type="password" x-model="form.password_confirmation" required
                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-slate-200 focus:outline-none focus:border-blue-500">
        </div>

        <button type="submit" :disabled="loading"
            class="w-full py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded font-semibold transition">
            <span x-show="!loading">Reset Password</span>
            <span x-show="loading">Resetting...</span>
        </button>
    </form>
</div>

<script>
function resetForm() {
    return {
        form: {
            email: '{{ $email ?? '' }}',
            token: '{{ $token ?? '' }}',
            password: '',
            password_confirmation: ''
        },
        error: null,
        success: null,
        loading: false,
        async submit() {
            this.loading = true;
            this.error = null;
            this.success = null;
            try {
                const res = await fetch('/reset-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || Object.values(data.errors || {}).flat()[0] || 'Failed.';
                } else {
                    this.success = data.message + ' Redirecting to login...';
                    setTimeout(() => window.location.href = '/login', 2000);
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
    <a href="/login" class="text-blue-400 hover:underline">Back to login</a>
@endsection

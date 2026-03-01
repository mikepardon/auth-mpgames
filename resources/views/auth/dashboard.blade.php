@extends('layouts.auth')
@section('title', 'Dashboard - MPGames')

@section('content')
<div x-data="{ showChangePassword: false, showDeleteAccount: false }">
    <h2 class="text-xl font-semibold text-center mb-4">Welcome, {{ auth()->user()->username }}</h2>

    <div class="space-y-3 text-sm">
        <div class="flex justify-between py-2 border-b border-slate-800">
            <span class="text-slate-400">Username</span>
            <span>{{ auth()->user()->username }}</span>
        </div>
        <div class="flex justify-between py-2 border-b border-slate-800">
            <span class="text-slate-400">Email</span>
            <span>{{ auth()->user()->email }}</span>
        </div>
        <div class="flex justify-between py-2 border-b border-slate-800">
            <span class="text-slate-400">Verified</span>
            <span>{{ auth()->user()->email_verified_at ? 'Yes' : 'No' }}</span>
        </div>
        <div class="flex justify-between py-2 border-b border-slate-800">
            <span class="text-slate-400">Member since</span>
            <span>{{ auth()->user()->created_at->format('M j, Y') }}</span>
        </div>
    </div>

    <div class="mt-6 space-y-3">
        <button @click="showChangePassword = !showChangePassword"
            class="w-full py-2 bg-slate-800 hover:bg-slate-700 rounded text-sm transition">
            Change Password
        </button>

        <div x-show="showChangePassword" x-data="changePasswordForm()" class="bg-slate-800 rounded p-4">
            <template x-if="cpError">
                <div class="bg-red-900/50 border border-red-700 text-red-300 px-3 py-1 rounded mb-3 text-xs" x-text="cpError"></div>
            </template>
            <template x-if="cpSuccess">
                <div class="bg-green-900/50 border border-green-700 text-green-300 px-3 py-1 rounded mb-3 text-xs" x-text="cpSuccess"></div>
            </template>

            <form @submit.prevent="submitChangePassword">
                <input type="password" x-model="cpForm.current_password" placeholder="Current password" required
                    class="w-full px-3 py-2 mb-2 bg-slate-900 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                <input type="password" x-model="cpForm.new_password" placeholder="New password" required minlength="4"
                    class="w-full px-3 py-2 mb-2 bg-slate-900 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                <input type="password" x-model="cpForm.new_password_confirmation" placeholder="Confirm new password" required
                    class="w-full px-3 py-2 mb-3 bg-slate-900 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                <button type="submit" :disabled="cpLoading"
                    class="w-full py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded text-sm font-medium transition">
                    Update Password
                </button>
            </form>
        </div>

        <button @click="showDeleteAccount = !showDeleteAccount"
            class="w-full py-2 bg-red-900/30 hover:bg-red-900/50 text-red-400 border border-red-900 rounded text-sm transition">
            Delete Account
        </button>

        <div x-show="showDeleteAccount" x-data="deleteAccountForm()" class="bg-red-950/50 border border-red-900 rounded p-4">
            <p class="text-xs text-red-300 mb-3">This action is permanent. All your data will be deleted and connected game accounts will be notified.</p>

            <template x-if="delError">
                <div class="bg-red-900/50 border border-red-700 text-red-300 px-3 py-1 rounded mb-3 text-xs" x-text="delError"></div>
            </template>

            <form @submit.prevent="submitDelete">
                @if(auth()->user()->password)
                <input type="password" x-model="delForm.password" placeholder="Enter your password to confirm" required
                    class="w-full px-3 py-2 mb-3 bg-slate-900 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-red-500">
                @endif
                <button type="submit" :disabled="delLoading"
                    class="w-full py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 rounded text-sm font-medium transition">
                    <span x-show="!delLoading">Permanently Delete My Account</span>
                    <span x-show="delLoading">Deleting...</span>
                </button>
            </form>
        </div>

        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="w-full py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-800 rounded text-sm transition">
                Sign Out
            </button>
        </form>
    </div>
</div>

<script>
function deleteAccountForm() {
    return {
        delForm: { password: '' },
        delError: null,
        delLoading: false,
        async submitDelete() {
            this.delLoading = true;
            this.delError = null;
            try {
                const res = await fetch('/delete-account', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(this.delForm)
                });
                const data = await res.json();
                if (!res.ok) {
                    this.delError = data.message || Object.values(data.errors || {}).flat()[0] || 'Failed.';
                } else {
                    window.location.href = data.redirect || '/login';
                }
            } catch (e) {
                this.delError = 'Network error.';
            } finally {
                this.delLoading = false;
            }
        }
    };
}

function changePasswordForm() {
    return {
        cpForm: { current_password: '', new_password: '', new_password_confirmation: '' },
        cpError: null,
        cpSuccess: null,
        cpLoading: false,
        async submitChangePassword() {
            this.cpLoading = true;
            this.cpError = null;
            this.cpSuccess = null;
            try {
                const res = await fetch('/change-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(this.cpForm)
                });
                const data = await res.json();
                if (!res.ok) {
                    this.cpError = data.message || Object.values(data.errors || {}).flat()[0] || 'Failed.';
                } else {
                    this.cpSuccess = data.message;
                    this.cpForm = { current_password: '', new_password: '', new_password_confirmation: '' };
                }
            } catch (e) {
                this.cpError = 'Network error.';
            } finally {
                this.cpLoading = false;
            }
        }
    };
}
</script>
@endsection

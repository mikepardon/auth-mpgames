@extends('layouts.admin')
@section('title', $user->username . ' - Admin - MPGames')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="/admin/users" class="text-slate-400 hover:text-slate-200 transition">&larr;</a>
    <h2 class="text-2xl font-semibold">{{ $user->username }}</h2>
    @if($user->is_admin)
        <span class="px-2 py-0.5 bg-amber-600/20 text-amber-400 border border-amber-800 rounded text-xs">Admin</span>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- User Info -->
    <div class="bg-slate-900 border border-slate-800 rounded-lg p-5">
        <h3 class="text-sm font-medium text-slate-400 uppercase mb-4">User Info</h3>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between py-1 border-b border-slate-800">
                <span class="text-slate-400">ID</span>
                <span>{{ $user->id }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-800">
                <span class="text-slate-400">Email</span>
                <span>{{ $user->email }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-800">
                <span class="text-slate-400">Verified</span>
                <span>{{ $user->email_verified_at ? $user->email_verified_at->format('M j, Y H:i') : 'No' }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-800">
                <span class="text-slate-400">Has Password</span>
                <span>{{ $user->password ? 'Yes' : 'No (Social only)' }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-800">
                <span class="text-slate-400">Google Linked</span>
                <span>{{ $user->google_id ? 'Yes' : 'No' }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-800">
                <span class="text-slate-400">Apple Linked</span>
                <span>{{ $user->apple_id ? 'Yes' : 'No' }}</span>
            </div>
            <div class="flex justify-between py-1">
                <span class="text-slate-400">Created</span>
                <span>{{ $user->created_at->format('M j, Y H:i') }}</span>
            </div>
        </div>

        @if($user->id !== auth()->id())
        <form method="POST" action="/admin/users/{{ $user->id }}/toggle-admin" class="mt-4">
            @csrf
            <button type="submit"
                class="w-full py-2 {{ $user->is_admin ? 'bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-800' : 'bg-amber-600/20 hover:bg-amber-600/30 text-amber-400 border border-amber-800' }} rounded text-sm transition">
                {{ $user->is_admin ? 'Remove Admin' : 'Make Admin' }}
            </button>
        </form>
        @endif

        {{-- Set Password --}}
        <div class="mt-5 pt-5 border-t border-slate-800">
            <h3 class="text-sm font-medium text-slate-400 uppercase mb-3">Set Password</h3>
            <form method="POST" action="/admin/users/{{ $user->id }}/set-password">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">New Password</label>
                        <input type="password" name="password" required placeholder="Minimum 4 characters"
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                        @error('password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" required placeholder="Repeat password"
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                    </div>
                    <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm font-medium transition">
                        Set Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- OAuth Tokens -->
    <div class="bg-slate-900 border border-slate-800 rounded-lg p-5">
        <h3 class="text-sm font-medium text-slate-400 uppercase mb-4">Active OAuth Tokens</h3>
        @if($oauthTokens->isEmpty())
            <p class="text-sm text-slate-500">No active tokens.</p>
        @else
            <div class="space-y-2">
                @foreach($oauthTokens as $token)
                <div class="bg-slate-800 rounded p-3 text-sm">
                    <div class="font-medium">{{ $token->client_name }}</div>
                    <div class="text-xs text-slate-400 mt-1">
                        Created: {{ \Carbon\Carbon::parse($token->created_at)->format('M j, Y H:i') }}
                        @if($token->expires_at)
                            &middot; Expires: {{ \Carbon\Carbon::parse($token->expires_at)->format('M j, Y H:i') }}
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Audit Log -->
<div class="bg-slate-900 border border-slate-800 rounded-lg p-5">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-medium text-slate-400 uppercase">Audit Log</h3>
        <a href="/admin/audit-logs?user_id={{ $user->id }}" class="text-xs text-blue-400 hover:underline">View All</a>
    </div>

    @if($auditLogs->isEmpty())
        <p class="text-sm text-slate-500">No audit log entries.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-800/50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-400">Action</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-400">IP</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-400">Metadata</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-400">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach($auditLogs as $log)
                <tr>
                    <td class="px-3 py-2">
                        <span class="px-2 py-0.5 bg-slate-800 rounded text-xs">{{ $log->action }}</span>
                    </td>
                    <td class="px-3 py-2 text-slate-400 text-xs">{{ $log->ip_address }}</td>
                    <td class="px-3 py-2 text-slate-400 text-xs">
                        @if($log->metadata)
                            {{ json_encode($log->metadata) }}
                        @endif
                    </td>
                    <td class="px-3 py-2 text-slate-400 text-xs">{{ $log->created_at->format('M j, Y H:i:s') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection

@extends('layouts.admin')
@section('title', 'Users - Admin - MPGames')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold">Users</h2>
</div>

<form method="GET" action="/admin/users" class="mb-4">
    <div class="flex gap-2">
        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by username or email..."
            class="flex-1 px-3 py-2 bg-slate-900 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm font-medium transition">Search</button>
        @if($search)
            <a href="/admin/users" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded text-sm transition">Clear</a>
        @endif
    </div>
</form>

<div class="bg-slate-900 border border-slate-800 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-800/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Username</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Email</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Verified</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Admin</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Created</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse($users as $user)
            <tr class="hover:bg-slate-800/30">
                <td class="px-4 py-3">
                    <a href="/admin/users/{{ $user->id }}" class="text-blue-400 hover:underline">{{ $user->username }}</a>
                </td>
                <td class="px-4 py-3 text-slate-400">{{ $user->email }}</td>
                <td class="px-4 py-3">
                    @if($user->email_verified_at)
                        <span class="text-green-400">Yes</span>
                    @else
                        <span class="text-red-400">No</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($user->is_admin)
                        <span class="text-amber-400">Yes</span>
                    @else
                        <span class="text-slate-500">No</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-slate-400">{{ $user->created_at->format('M j, Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-slate-500">No users found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $users->links() }}
</div>
@endsection

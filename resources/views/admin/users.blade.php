@extends('layouts.admin')
@section('title', 'Users - Admin - MPGames')

@section('content')
<div x-data="{ showCreate: false }">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold">Users</h2>
        <button @click="showCreate = !showCreate"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm font-medium transition">
            <span x-show="!showCreate">Create User</span>
            <span x-show="showCreate">Cancel</span>
        </button>
    </div>

    {{-- Create user form --}}
    <div x-show="showCreate" x-cloak class="bg-slate-900 border border-slate-800 rounded-lg p-5 mb-6">
        <h3 class="text-sm font-medium text-slate-400 uppercase mb-4">Create New User</h3>
        <form method="POST" action="/admin/users">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Username</label>
                    <input type="text" name="username" required value="{{ old('username') }}" placeholder="4-20 alphanumeric characters"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                    @error('username') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Email</label>
                    <input type="email" name="email" required value="{{ old('email') }}" placeholder="user@example.com"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                    @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="Minimum 4 characters"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                    @error('password') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" required placeholder="Repeat password"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                </div>
            </div>
            <div class="flex items-center gap-6 mb-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="mark_verified" value="1"
                        class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-slate-300">Mark email as verified</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_admin" value="1"
                        class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-slate-300">Make admin</span>
                </label>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm font-medium transition">
                Create User
            </button>
        </form>
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
</div>
@endsection

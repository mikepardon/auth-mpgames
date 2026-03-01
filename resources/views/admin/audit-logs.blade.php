@extends('layouts.admin')
@section('title', 'Audit Logs - Admin - MPGames')

@section('content')
<h2 class="text-2xl font-semibold mb-6">Audit Logs</h2>

<form method="GET" action="/admin/audit-logs" class="mb-4 flex gap-2 flex-wrap">
    <select name="action"
        class="px-3 py-2 bg-slate-900 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
        <option value="">All Actions</option>
        @foreach($actions as $a)
            <option value="{{ $a }}" {{ $action === $a ? 'selected' : '' }}>{{ $a }}</option>
        @endforeach
    </select>
    <input type="text" name="user_id" value="{{ $userId ?? '' }}" placeholder="User ID..."
        class="w-32 px-3 py-2 bg-slate-900 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm font-medium transition">Filter</button>
    @if($action || $userId)
        <a href="/admin/audit-logs" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded text-sm transition">Clear</a>
    @endif
</form>

<div class="bg-slate-900 border border-slate-800 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-800/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">User</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">IP</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Metadata</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-800/30">
                    <td class="px-4 py-3 text-slate-400 text-xs whitespace-nowrap">{{ $log->created_at->format('M j, Y H:i:s') }}</td>
                    <td class="px-4 py-3">
                        @if($log->user)
                            <a href="/admin/users/{{ $log->user_id }}" class="text-blue-400 hover:underline">{{ $log->user->username }}</a>
                        @elseif($log->user_id)
                            <span class="text-slate-500">Deleted (#{{ $log->user_id }})</span>
                        @else
                            <span class="text-slate-500">System</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 bg-slate-800 rounded text-xs">{{ $log->action }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-400 text-xs">{{ $log->ip_address }}</td>
                    <td class="px-4 py-3 text-slate-400 text-xs max-w-xs truncate">
                        @if($log->metadata)
                            {{ json_encode($log->metadata) }}
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">No audit log entries.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $logs->links() }}
</div>
@endsection

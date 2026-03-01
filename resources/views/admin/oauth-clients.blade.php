@extends('layouts.admin')
@section('title', 'OAuth Clients - Admin - MPGames')

@section('content')
<h2 class="text-2xl font-semibold mb-6">OAuth Clients</h2>

<div class="bg-slate-900 border border-slate-800 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-800/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Redirect URIs</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Webhook URL</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($clients as $client)
                <tr class="hover:bg-slate-800/30">
                    <td class="px-4 py-3 font-medium">{{ $client->name }}</td>
                    <td class="px-4 py-3 text-xs text-slate-400 font-mono">{{ Str::limit($client->id, 12) }}</td>
                    <td class="px-4 py-3 text-xs text-slate-400 max-w-xs truncate">{{ $client->redirect_uris }}</td>
                    <td class="px-4 py-3 text-xs">
                        @if($client->webhook_url)
                            <span class="text-green-400">{{ $client->webhook_url }}</span>
                        @else
                            <span class="text-slate-500">Not configured</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-400 text-xs">{{ \Carbon\Carbon::parse($client->created_at)->format('M j, Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">No OAuth clients registered.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

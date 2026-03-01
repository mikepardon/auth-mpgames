@extends('layouts.admin')
@section('title', 'OAuth Clients - Admin - MPGames')

@section('content')
<div x-data="{ showCreate: false }">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold">OAuth Clients</h2>
        <button @click="showCreate = !showCreate"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm font-medium transition">
            <span x-show="!showCreate">New Client</span>
            <span x-show="showCreate">Cancel</span>
        </button>
    </div>

    {{-- New client credentials flash --}}
    @if(session('newClient'))
        <div class="bg-green-900/50 border border-green-700 rounded-lg p-5 mb-6">
            <h3 class="text-green-300 font-medium mb-3">Client Created — Save These Credentials</h3>
            <div class="space-y-2 text-sm font-mono">
                <div>
                    <span class="text-slate-400">Client ID:</span>
                    <span class="text-green-300 select-all">{{ session('newClient')['id'] }}</span>
                </div>
                @if(session('newClient')['secret'])
                <div>
                    <span class="text-slate-400">Client Secret:</span>
                    <span class="text-green-300 select-all">{{ session('newClient')['secret'] }}</span>
                </div>
                @endif
                @if(session('newClient')['webhook_secret'])
                <div>
                    <span class="text-slate-400">Webhook Secret:</span>
                    <span class="text-green-300 select-all">{{ session('newClient')['webhook_secret'] }}</span>
                </div>
                @endif
            </div>
            <p class="text-xs text-amber-400 mt-3">These secrets will not be shown again.</p>
        </div>
    @endif

    {{-- Create form --}}
    <div x-show="showCreate" x-cloak class="bg-slate-900 border border-slate-800 rounded-lg p-5 mb-6">
        <h3 class="text-sm font-medium text-slate-400 uppercase mb-4">Register New Client</h3>
        <form method="POST" action="/admin/oauth-clients">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Client Name</label>
                    <input type="text" name="name" required placeholder="e.g. Trusted Advisors"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Redirect URI</label>
                    <input type="url" name="redirect_uri" required placeholder="https://game.mpgames.io/auth/callback"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Webhook URL <span class="text-slate-500">(optional)</span></label>
                    <input type="url" name="webhook_url" placeholder="https://game.mpgames.io/api/webhooks/auth"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer py-2">
                        <input type="checkbox" name="is_public" value="1"
                            class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-slate-300">Public client (no secret, for SPAs)</span>
                    </label>
                </div>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm font-medium transition">
                Create Client
            </button>
        </form>
    </div>

    {{-- Client list --}}
    <div class="bg-slate-900 border border-slate-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Client ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Redirect URIs</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Webhook</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400 uppercase">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($clients as $client)
                    <tr class="hover:bg-slate-800/30">
                        <td class="px-4 py-3 font-medium">
                            <a href="/admin/oauth-clients/{{ $client->id }}" class="text-blue-400 hover:underline">{{ $client->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400 font-mono">{{ Str::limit($client->id, 16) }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if($client->secret)
                                <span class="text-slate-300">Confidential</span>
                            @else
                                <span class="text-amber-400">Public</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400 max-w-xs truncate">{{ $client->redirect_uris }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if($client->webhook_url)
                                <span class="text-green-400">Configured</span>
                            @else
                                <span class="text-slate-500">None</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-400 text-xs">{{ \Carbon\Carbon::parse($client->created_at)->format('M j, Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">No OAuth clients registered.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

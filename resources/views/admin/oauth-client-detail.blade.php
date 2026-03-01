@extends('layouts.admin')
@section('title', $client->name . ' - OAuth Client - Admin')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="/admin/oauth-clients" class="text-slate-400 hover:text-slate-200 transition">&larr;</a>
    <h2 class="text-2xl font-semibold">{{ $client->name }}</h2>
</div>

{{-- Regenerated secret flash --}}
@if(session('newSecret'))
    <div class="bg-green-900/50 border border-green-700 rounded-lg p-5 mb-6">
        <h3 class="text-green-300 font-medium mb-2">New Client Secret</h3>
        <p class="text-sm font-mono text-green-300 select-all">{{ session('newSecret') }}</p>
        <p class="text-xs text-amber-400 mt-2">This secret will not be shown again. Update your game's configuration now.</p>
    </div>
@endif

@if(session('newWebhookSecret'))
    <div class="bg-green-900/50 border border-green-700 rounded-lg p-5 mb-6">
        <h3 class="text-green-300 font-medium mb-2">New Webhook Secret</h3>
        <p class="text-sm font-mono text-green-300 select-all">{{ session('newWebhookSecret') }}</p>
        <p class="text-xs text-amber-400 mt-2">This secret will not be shown again. Update your game's webhook configuration now.</p>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Client Info & Edit --}}
    <div class="bg-slate-900 border border-slate-800 rounded-lg p-5">
        <h3 class="text-sm font-medium text-slate-400 uppercase mb-4">Client Settings</h3>

        <form method="POST" action="/admin/oauth-clients/{{ $client->id }}">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Client ID</label>
                    <div class="px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-300 font-mono select-all">
                        {{ $client->id }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-400 mb-1">Name</label>
                    <input type="text" name="name" value="{{ $client->name }}" required
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm text-slate-400 mb-1">Redirect URI</label>
                    @php
                        $redirects = json_decode($client->redirect_uris, true);
                        $firstRedirect = is_array($redirects) ? ($redirects[0] ?? '') : $client->redirect_uris;
                    @endphp
                    <input type="url" name="redirect_uri" value="{{ $firstRedirect }}" required
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm text-slate-400 mb-1">Webhook URL <span class="text-slate-500">(optional)</span></label>
                    <input type="url" name="webhook_url" value="{{ $client->webhook_url }}"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                </div>

                <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 rounded text-sm font-medium transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Info & Actions --}}
    <div class="space-y-6">
        {{-- Stats --}}
        <div class="bg-slate-900 border border-slate-800 rounded-lg p-5">
            <h3 class="text-sm font-medium text-slate-400 uppercase mb-4">Info</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between py-1 border-b border-slate-800">
                    <span class="text-slate-400">Type</span>
                    <span>{{ $client->secret ? 'Confidential' : 'Public (no secret)' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-800">
                    <span class="text-slate-400">Active Tokens</span>
                    <span>{{ $tokenCount }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-800">
                    <span class="text-slate-400">Webhook</span>
                    <span>{{ $client->webhook_url ? 'Configured' : 'None' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-800">
                    <span class="text-slate-400">Grant Types</span>
                    @php
                        $grants = json_decode($client->grant_types, true);
                    @endphp
                    <span class="text-xs">{{ is_array($grants) ? implode(', ', $grants) : $client->grant_types }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-400">Created</span>
                    <span>{{ \Carbon\Carbon::parse($client->created_at)->format('M j, Y H:i') }}</span>
                </div>
            </div>
        </div>

        {{-- Secret actions --}}
        <div class="bg-slate-900 border border-slate-800 rounded-lg p-5">
            <h3 class="text-sm font-medium text-slate-400 uppercase mb-4">Secrets</h3>
            <div class="space-y-3">
                @if($client->secret)
                <div x-data="{ confirm: false }">
                    <button x-show="!confirm" @click="confirm = true"
                        class="w-full py-2 bg-amber-600/20 hover:bg-amber-600/30 text-amber-400 border border-amber-800 rounded text-sm transition">
                        Regenerate Client Secret
                    </button>
                    <form x-show="confirm" method="POST" action="/admin/oauth-clients/{{ $client->id }}/regenerate-secret" class="flex gap-2">
                        @csrf
                        <button type="submit" class="flex-1 py-2 bg-amber-600 hover:bg-amber-700 rounded text-sm font-medium transition">
                            Confirm Regenerate
                        </button>
                        <button type="button" @click="confirm = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded text-sm transition">
                            Cancel
                        </button>
                    </form>
                </div>
                @endif

                @if($client->webhook_url)
                <div x-data="{ confirm: false }">
                    <button x-show="!confirm" @click="confirm = true"
                        class="w-full py-2 bg-amber-600/20 hover:bg-amber-600/30 text-amber-400 border border-amber-800 rounded text-sm transition">
                        Regenerate Webhook Secret
                    </button>
                    <form x-show="confirm" method="POST" action="/admin/oauth-clients/{{ $client->id }}/regenerate-webhook-secret" class="flex gap-2">
                        @csrf
                        <button type="submit" class="flex-1 py-2 bg-amber-600 hover:bg-amber-700 rounded text-sm font-medium transition">
                            Confirm Regenerate
                        </button>
                        <button type="button" @click="confirm = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded text-sm transition">
                            Cancel
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        {{-- Revoke --}}
        <div class="bg-slate-900 border border-slate-800 rounded-lg p-5" x-data="{ confirm: false }">
            <h3 class="text-sm font-medium text-red-400 uppercase mb-4">Danger Zone</h3>
            <button x-show="!confirm" @click="confirm = true"
                class="w-full py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-800 rounded text-sm transition">
                Revoke Client
            </button>
            <div x-show="confirm" class="space-y-3">
                <p class="text-xs text-red-300">This will revoke the client and all its tokens. Users will need to re-authenticate. This cannot be undone.</p>
                <form method="POST" action="/admin/oauth-clients/{{ $client->id }}" class="flex gap-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex-1 py-2 bg-red-600 hover:bg-red-700 rounded text-sm font-medium transition">
                        Confirm Revoke
                    </button>
                    <button type="button" @click="confirm = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded text-sm transition">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

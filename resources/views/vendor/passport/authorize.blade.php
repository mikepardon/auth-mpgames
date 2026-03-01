<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Authorize - MPGames</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-200 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-400">MPGames</h1>
            <p class="text-slate-400 text-sm mt-1">Authorization Request</p>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-lg p-6 shadow-xl">
            <h2 class="text-xl font-semibold text-center mb-2">Authorize {{ $client->name }}</h2>
            <p class="text-slate-400 text-sm text-center mb-6">
                <strong class="text-slate-200">{{ $client->name }}</strong> is requesting access to your account.
            </p>

            @if (count($scopes) > 0)
                <div class="mb-6">
                    <p class="text-sm text-slate-400 mb-2">This application will be able to:</p>
                    <ul class="space-y-1">
                        @foreach ($scopes as $scope)
                            <li class="flex items-center text-sm">
                                <svg class="w-4 h-4 text-blue-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $scope->description }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex space-x-3">
                <form method="post" action="{{ route('passport.authorizations.deny') }}" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="state" value="{{ $request->state }}">
                    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                    @if ($request->input('auth_token'))
                        <input type="hidden" name="auth_token" value="{{ $request->input('auth_token') }}">
                    @endif
                    <button type="submit"
                        class="w-full py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded font-medium transition">
                        Deny
                    </button>
                </form>

                <form method="post" action="{{ route('passport.authorizations.approve') }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="state" value="{{ $request->state }}">
                    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                    @if ($request->input('auth_token'))
                        <input type="hidden" name="auth_token" value="{{ $request->input('auth_token') }}">
                    @endif
                    <button type="submit"
                        class="w-full py-2 bg-blue-600 hover:bg-blue-700 rounded font-semibold transition">
                        Authorize
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center mt-6 text-xs text-slate-500">
            Signed in as {{ auth()->user()->username }}
        </p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MPGames Auth')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-200 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-400">MPGames</h1>
            <p class="text-slate-400 text-sm mt-1">Central Authentication</p>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-lg p-6 shadow-xl">
            @yield('content')
        </div>

        <div class="text-center mt-6 text-sm text-slate-500">
            @yield('footer')
        </div>
    </div>
</body>
</html>

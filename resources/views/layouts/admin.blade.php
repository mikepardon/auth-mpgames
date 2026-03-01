<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - MPGames')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-200">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-56 bg-slate-900 border-r border-slate-800 flex flex-col">
            <div class="p-4 border-b border-slate-800">
                <h1 class="text-lg font-bold text-blue-400">MPGames</h1>
                <p class="text-xs text-slate-500">Admin Panel</p>
            </div>
            <nav class="flex-1 p-3 space-y-1">
                <a href="/admin" class="block px-3 py-2 rounded text-sm {{ request()->is('admin') && !request()->is('admin/*') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }} transition">
                    Dashboard
                </a>
                <a href="/admin/users" class="block px-3 py-2 rounded text-sm {{ request()->is('admin/users*') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }} transition">
                    Users
                </a>
                <a href="/admin/audit-logs" class="block px-3 py-2 rounded text-sm {{ request()->is('admin/audit-logs*') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }} transition">
                    Audit Logs
                </a>
                <a href="/admin/oauth-clients" class="block px-3 py-2 rounded text-sm {{ request()->is('admin/oauth-clients*') ? 'bg-blue-600/20 text-blue-400' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }} transition">
                    OAuth Clients
                </a>
            </nav>
            <div class="p-3 border-t border-slate-800">
                <div class="text-xs text-slate-500 mb-2">{{ auth()->user()->username }}</div>
                <a href="/dashboard" class="block px-3 py-2 rounded text-sm text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition">
                    Back to Dashboard
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 overflow-auto">
            @if(session('success'))
                <div class="bg-green-900/50 border border-green-700 text-green-300 px-4 py-2 rounded mb-4 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-2 rounded mb-4 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>

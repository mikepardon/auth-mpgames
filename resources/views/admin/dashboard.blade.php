@extends('layouts.admin')
@section('title', 'Admin Dashboard - MPGames')

@section('content')
<h2 class="text-2xl font-semibold mb-6">Dashboard</h2>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-slate-900 border border-slate-800 rounded-lg p-5">
        <div class="text-sm text-slate-400">Total Users</div>
        <div class="text-3xl font-bold text-blue-400 mt-1">{{ number_format($totalUsers) }}</div>
    </div>
    <div class="bg-slate-900 border border-slate-800 rounded-lg p-5">
        <div class="text-sm text-slate-400">Verified Users</div>
        <div class="text-3xl font-bold text-green-400 mt-1">{{ number_format($verifiedUsers) }}</div>
    </div>
    <div class="bg-slate-900 border border-slate-800 rounded-lg p-5">
        <div class="text-sm text-slate-400">Registrations (7d)</div>
        <div class="text-3xl font-bold text-purple-400 mt-1">{{ number_format($recentRegistrations) }}</div>
    </div>
    <div class="bg-slate-900 border border-slate-800 rounded-lg p-5">
        <div class="text-sm text-slate-400">Logins (7d)</div>
        <div class="text-3xl font-bold text-amber-400 mt-1">{{ number_format($recentLogins) }}</div>
    </div>
</div>
@endsection

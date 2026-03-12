<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

// Home - redirect to login
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
});

// Dashboard (simple authenticated landing page)
Route::get('/dashboard', function () {
    return view('auth.dashboard');
})->middleware('auth');

// Auth pages (views)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::get('/verify-email', [AuthController::class, 'showVerify'])->name('verify-email');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password');
    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('reset-password');
});

// Auth API endpoints (JSON)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Authenticated actions
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/delete-account', [AuthController::class, 'deleteAccount']);
});

// Admin dashboard
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users', [AdminController::class, 'createUser']);
    Route::get('/users/{id}', [AdminController::class, 'userDetail']);
    Route::post('/users/{id}/toggle-admin', [AdminController::class, 'toggleAdmin']);
    Route::post('/users/{id}/set-password', [AdminController::class, 'setPassword']);
    Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
    Route::get('/oauth-clients', [AdminController::class, 'oauthClients']);
    Route::post('/oauth-clients', [AdminController::class, 'createOauthClient']);
    Route::get('/oauth-clients/{id}', [AdminController::class, 'showOauthClient']);
    Route::put('/oauth-clients/{id}', [AdminController::class, 'updateOauthClient']);
    Route::post('/oauth-clients/{id}/regenerate-secret', [AdminController::class, 'regenerateOauthSecret']);
    Route::post('/oauth-clients/{id}/regenerate-webhook-secret', [AdminController::class, 'regenerateWebhookSecret']);
    Route::delete('/oauth-clients/{id}', [AdminController::class, 'revokeOauthClient']);
});

// Social auth (SSO)
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
Route::get('/auth/apple/redirect', [SocialAuthController::class, 'redirectToApple']);
Route::match(['get', 'post'], '/auth/apple/callback', [SocialAuthController::class, 'handleAppleCallback']);
Route::get('/auth/apple/complete', [SocialAuthController::class, 'completeAppleAuth']);

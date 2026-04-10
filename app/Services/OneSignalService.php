<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    private string $appId;
    private string $restApiKey;

    public function __construct()
    {
        $this->appId = config('services.onesignal.app_id') ?? '';
        $this->restApiKey = config('services.onesignal.rest_api_key') ?? '';
    }

    public function ensureEmailSubscription(string $email): void
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $this->restApiKey,
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/players', [
            'app_id' => $this->appId,
            'device_type' => 11,
            'identifier' => $email,
        ]);

        if ($response->failed()) {
            Log::error('OneSignal: Failed to ensure email subscription', [
                'email' => $email,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    public function sendEmail(string $toEmail, string $subject, string $htmlBody): void
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $this->restApiKey,
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => $this->appId,
            'include_email_tokens' => [$toEmail],
            'email_subject' => $subject,
            'email_body' => $htmlBody,
            'email_from_name' => 'MPGames',
            'email_from_address' => 'hello@mpgames.io',
        ]);

        if ($response->failed()) {
            Log::error('OneSignal: Failed to send email', [
                'email' => $toEmail,
                'subject' => $subject,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    private string $appId;
    private string $apiKey;

    public function __construct()
    {
        $this->appId = config('services.onesignal.app_id', '');
        $this->apiKey = config('services.onesignal.rest_api_key', '');
    }

    public function registerEmail(User $user): void
    {
        if (!$this->appId || !$this->apiKey || !$user->email) {
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->apiKey,
            ])->post('https://onesignal.com/api/v1/players', [
                'app_id' => $this->appId,
                'device_type' => 11,
                'identifier' => $user->email,
                'tags' => ['user_id' => (string) $user->id],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['id'])) {
                    $user->update(['onesignal_email_token' => $data['id']]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('OneSignal email registration failed', ['error' => $e->getMessage()]);
        }
    }

    public function sendEmailToUser(User $user, string $subject, string $htmlBody): void
    {
        if (!$this->appId || !$this->apiKey) {
            return;
        }

        if (!$user->onesignal_email_token) {
            return;
        }

        try {
            Http::withHeaders([
                'Authorization' => 'Basic ' . $this->apiKey,
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => $this->appId,
                'include_email_tokens' => [$user->onesignal_email_token],
                'email_subject' => $subject,
                'email_body' => $htmlBody,
            ]);
        } catch (\Exception $e) {
            Log::warning('OneSignal email send failed', ['error' => $e->getMessage()]);
        }
    }
}

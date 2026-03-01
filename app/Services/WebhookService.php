<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use Illuminate\Support\Facades\DB;

class WebhookService
{
    public function notifyClients(string $event, array $payload): void
    {
        $clients = DB::table('oauth_clients')
            ->whereNotNull('webhook_url')
            ->where('revoked', false)
            ->get(['webhook_url', 'webhook_secret']);

        foreach ($clients as $client) {
            SendWebhookJob::dispatch(
                $client->webhook_url,
                $client->webhook_secret,
                $event,
                $payload,
            );
        }
    }
}

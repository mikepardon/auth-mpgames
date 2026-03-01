<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        public string $url,
        public ?string $secret,
        public string $event,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $body = json_encode([
            'event' => $this->event,
            'data' => $this->payload,
            'timestamp' => now()->toIso8601String(),
        ]);

        $headers = ['Content-Type' => 'application/json'];

        if ($this->secret) {
            $headers['X-Webhook-Signature'] = hash_hmac('sha256', $body, $this->secret);
        }

        $response = Http::withHeaders($headers)
            ->withBody($body, 'application/json')
            ->timeout(15)
            ->post($this->url);

        if ($response->failed()) {
            Log::warning('Webhook delivery failed', [
                'url' => $this->url,
                'event' => $this->event,
                'status' => $response->status(),
            ]);
            $this->fail(new \RuntimeException("Webhook returned {$response->status()}"));
        }
    }
}

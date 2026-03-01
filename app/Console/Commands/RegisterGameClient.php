<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterGameClient extends Command
{
    protected $signature = 'game:register
        {name : The name of the game client}
        {redirect : The redirect URI for the client}
        {--public : Create a public client (no secret, for SPAs)}
        {--webhook= : Webhook URL for receiving events (e.g. user deletion)}';

    protected $description = 'Register a new game as an OAuth2 client';

    public function handle(): int
    {
        $args = [
            '--name' => $this->argument('name'),
            '--redirect_uri' => $this->argument('redirect'),
            '--no-interaction' => true,
        ];

        if ($this->option('public')) {
            $args['--public'] = true;
        }

        Artisan::call('passport:client', $args);

        $output = Artisan::output();
        $this->line($output);

        if ($webhookUrl = $this->option('webhook')) {
            // Find the most recently created client with this name
            $client = DB::table('oauth_clients')
                ->where('name', $this->argument('name'))
                ->orderByDesc('created_at')
                ->first();

            if ($client) {
                $webhookSecret = Str::random(64);

                DB::table('oauth_clients')
                    ->where('id', $client->id)
                    ->update([
                        'webhook_url' => $webhookUrl,
                        'webhook_secret' => $webhookSecret,
                    ]);

                $this->info("Webhook URL: {$webhookUrl}");
                $this->info("Webhook Secret: {$webhookSecret}");
                $this->warn('Save this webhook secret — it cannot be retrieved later.');
            }
        }

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RegisterGameClient extends Command
{
    protected $signature = 'game:register
        {name : The name of the game client}
        {redirect : The redirect URI for the client}
        {--public : Create a public client (no secret, for SPAs)}';

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

        $this->line(Artisan::output());

        return Command::SUCCESS;
    }
}

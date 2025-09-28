<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:create-token {email} {--name=api-token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API token for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $tokenName = $this->option('name');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $token = $user->createToken($tokenName)->plainTextToken;

        $this->info("API Token created successfully!");
        $this->line("User: {$user->name} ({$user->email})");
        $this->line("Token: {$token}");
        $this->newLine();
        $this->comment("Use this token in your API requests as a Bearer token:");
        $this->line("Authorization: Bearer {$token}");

        return 0;
    }
}

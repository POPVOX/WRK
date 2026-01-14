<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateActivationLink extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'user:activation-link 
                            {email : The email address of the user}
                            {--expires=7 : Number of days until the link expires}
                            {--send : Send the activation email to the user}';

    /**
     * The console command description.
     */
    protected $description = 'Generate an activation link for an existing user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $expiresDays = (int) $this->option('expires');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email '{$email}' not found.");

            return self::FAILURE;
        }

        if ($user->activated_at) {
            $this->warn("User '{$user->name}' has already activated their account on {$user->activated_at->format('M j, Y')}.");
            if (! $this->confirm('Do you want to generate a new activation link anyway?')) {
                return self::SUCCESS;
            }
            // Reset activated_at to allow re-activation
            $user->activated_at = null;
        }

        // Generate token
        $token = Str::random(64);
        $expiresAt = now()->addDays($expiresDays);

        $user->update([
            'activation_token' => $token,
            'activation_token_expires_at' => $expiresAt,
        ]);

        $activationUrl = url("/activate/{$token}");

        $this->newLine();
        $this->info('✅ Activation link generated successfully!');
        $this->newLine();
        $this->line("<fg=cyan>User:</> {$user->name} ({$user->email})");
        $this->line("<fg=cyan>Expires:</> {$expiresAt->format('M j, Y g:i A')} ({$expiresDays} days)");
        $this->newLine();
        $this->line('<fg=yellow>Activation Link:</>');
        $this->line($activationUrl);
        $this->newLine();

        // Copy to clipboard if available (macOS)
        if (PHP_OS_FAMILY === 'Darwin') {
            exec("echo '{$activationUrl}' | pbcopy");
            $this->info('📋 Link copied to clipboard!');
        }

        if ($this->option('send')) {
            // TODO: Send activation email
            $this->info("📧 Email would be sent to {$user->email} (not implemented yet)");
        }

        return self::SUCCESS;
    }
}


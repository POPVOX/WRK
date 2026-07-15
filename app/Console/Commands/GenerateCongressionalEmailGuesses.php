<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CongressionalDirectory\CongressionalEmailGuessService;
use Illuminate\Console\Command;

class GenerateCongressionalEmailGuesses extends Command
{
    protected $signature = 'congressional:generate-email-guesses
        {--user= : User ID recorded as the generator}
        {--instructions=Database-wide formula enrichment : Evidence note stored with each guess}
        {--dry-run : Count resolvable profiles without writing}
        {--force : Confirm the database-wide write}';

    protected $description = 'Generate unverified provisional addresses for congressional profiles without known email evidence';

    public function handle(CongressionalEmailGuessService $guesses): int
    {
        $userId = (int) $this->option('user');
        if ($userId <= 0 || ! User::query()->whereKey($userId)->exists()) {
            $this->error('A valid --user ID is required for audit attribution.');

            return self::INVALID;
        }

        if ((bool) $this->option('dry-run')) {
            $this->info('Estimating database-wide provisional congressional addresses...');
            $result = $guesses->estimateAllProfiles();
            $this->resultTable($result, 'dry run');

            return self::SUCCESS;
        }

        if (! (bool) $this->option('force')) {
            $this->error('This command writes across the congressional directory. Re-run with --force after reviewing --dry-run.');

            return self::INVALID;
        }

        $this->info('Generating database-wide provisional congressional addresses...');
        $result = $guesses->generateForAllProfiles(
            $userId,
            trim((string) $this->option('instructions'))
        );
        $this->resultTable($result, 'write');

        return self::SUCCESS;
    }

    /** @param array<string,int> $result */
    protected function resultTable(array $result, string $mode): void
    {
        $this->table(
            ['Mode', 'Profiles', 'Already addressed', 'Candidates', 'Guessable/generated', 'House', 'Senate', 'Unresolved', 'Skipped'],
            [[
                $mode,
                number_format($result['total'] ?? 0),
                number_format($result['already_addressed'] ?? 0),
                number_format($result['candidates'] ?? 0),
                number_format($result['guessable'] ?? $result['generated'] ?? 0),
                number_format($result['house'] ?? 0),
                number_format($result['senate'] ?? 0),
                number_format($result['unresolved'] ?? 0),
                number_format($result['skipped'] ?? 0),
            ]]
        );
    }
}

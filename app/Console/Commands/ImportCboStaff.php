<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CongressionalDirectory\CboStaffDirectoryImporter;
use App\Services\CongressionalDirectory\CongressionalEmailGuessService;
use Illuminate\Console\Command;
use Throwable;

class ImportCboStaff extends Command
{
    protected $signature = 'congressional:import-cbo-staff
        {--source= : Optional local HTML path or staffing-page URL}
        {--user= : User ID recorded for provisional email generation}
        {--generate-emails : Add unverified first.last@cbo.gov suggestions for imported profiles}
        {--dry-run : Fetch, parse, and validate without writing}
        {--force : Confirm the directory write}';

    protected $description = 'Import CBO staff from the official organization and staffing directory';

    public function handle(CboStaffDirectoryImporter $importer, CongressionalEmailGuessService $guesses): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $generateEmails = (bool) $this->option('generate-emails');
        $userId = (int) $this->option('user');

        if (! $dryRun && ! (bool) $this->option('force')) {
            $this->error('Re-run with --force after reviewing --dry-run.');

            return self::INVALID;
        }

        if ($generateEmails && ($userId <= 0 || ! User::query()->whereKey($userId)->exists())) {
            $this->error('A valid --user ID is required with --generate-emails for audit attribution.');

            return self::INVALID;
        }

        try {
            $result = $importer->import(trim((string) $this->option('source')) ?: null, $dryRun);
            $emailResult = null;

            if (! $dryRun && $generateEmails) {
                $emailResult = $guesses->generateForProfileIds(
                    $result['profile_ids'],
                    $userId,
                    'Official CBO organization and staffing directory: '.$result['source_url'],
                    'CBO directory provisional guess.',
                    'cbo_import'
                );
            }
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Mode', 'Source', 'Snapshot', 'Parsed', 'Created', 'Updated', 'Marked historical', 'Emails generated'],
            [[
                $dryRun ? 'dry run' : 'import',
                $result['source_kind'],
                $result['snapshot_date'],
                number_format($result['parsed']),
                number_format($result['created']),
                number_format($result['updated']),
                number_format($result['historical']),
                number_format($emailResult['generated'] ?? 0),
            ]]
        );
        $this->line('Retrieved from: '.$result['retrieval_url']);

        return self::SUCCESS;
    }
}

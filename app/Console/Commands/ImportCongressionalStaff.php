<?php

namespace App\Console\Commands;

use App\Services\CongressionalDirectory\CongressionalStaffFeedImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class ImportCongressionalStaff extends Command
{
    protected $signature = 'congressional:import-staff
        {source? : Local .jsonl.gz path or HTTPS feed URL}
        {--limit= : Import only the first matching observations}
        {--chamber= : House or Senate}
        {--sha256= : Expected SHA-256 of the compressed feed}
        {--dry-run : Validate and count without writing to the database}';

    protected $description = 'Import public congressional staff role observations into the internal directory';

    public function handle(CongressionalStaffFeedImporter $importer): int
    {
        $sourceArgument = trim((string) $this->argument('source'));
        $source = $sourceArgument ?: trim((string) config('congressional.staff_feed_url'));
        $limitOption = $this->option('limit');
        $limit = $limitOption !== null && $limitOption !== '' ? (int) $limitOption : null;
        $chamber = trim((string) $this->option('chamber')) ?: null;
        $dryRun = (bool) $this->option('dry-run');
        $expectedSha256 = strtolower(trim((string) $this->option('sha256')));

        if ($chamber && ! in_array(strtolower($chamber), ['house', 'senate'], true)) {
            $this->error('The --chamber option must be House or Senate.');

            return self::INVALID;
        }

        $this->info(($dryRun ? 'Validating' : 'Importing').' congressional staff observations...');

        try {
            if ($sourceArgument === '' && $expectedSha256 === '') {
                $manifestUrl = trim((string) config('congressional.staff_feed_manifest_url'));
                $manifest = Http::timeout(30)->connectTimeout(10)->retry(3, 500)->get($manifestUrl)->throw()->json();
                $expectedSha256 = strtolower(trim((string) ($manifest['gzipSha256'] ?? '')));
                if (preg_match('/^[a-f0-9]{64}$/', $expectedSha256) !== 1) {
                    throw new \RuntimeException('Congressional staff feed manifest did not contain a valid gzipSha256.');
                }
            }

            $result = $importer->import($source, $limit, $chamber, $dryRun, $expectedSha256 ?: null);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Schema', 'Processed', 'Created', 'Updated', 'Mode'],
            [[
                $result['manifest']['schemaVersion'] ?? 'unknown',
                number_format($result['processed']),
                number_format($result['created']),
                number_format($result['updated']),
                $result['dry_run'] ? 'dry run' : 'import',
            ]]
        );

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateSqliteToPgsql extends Command
{
    protected $signature = 'db:migrate-sqlite-to-pgsql
                            {--source=sqlite : Source database connection}
                            {--target=pgsql : Target database connection}
                            {--source-file= : Absolute path to source SQLite file (overrides source connection database)}
                            {--tables= : Comma-separated table names to migrate (normalized names)}
                            {--dry-run : Show actions without writing}
                            {--truncate : Truncate target tables before import}
                            {--chunk=1000 : Rows per chunk to copy}';

    protected $description = 'Copy data from a SQLite connection into a PostgreSQL connection';

    protected array $skipTables = [
        'migrations',
    ];
    protected array $stringLengthCache = [];

    /**
     * FK-safe table order for current WRK schema.
     * Tables not listed here are migrated afterwards in alphabetical order.
     */
    protected array $tableOrder = [
        'cache',
        'jobs',
        'job_batches',
        'failed_jobs',
        'users',
        'organizations',
        'issues',
        'people',
        'projects',
        'meetings',
        'actions',
        'project_documents',
        'project_notes',
        'project_milestones',
        'project_questions',
        'project_decisions',
        'project_tasks',
        'project_events',
        'project_publications',
        'grants',
        'reporting_requirements',
        'grant_documents',
        'grant_reporting_schemas',
        'metric_calculations',
        'schema_chatbot_conversations',
        'feedback',
        'ai_fix_proposals',
        'ai_fix_audit_logs',
        'travel_profiles',
        'trips',
        'trip_travelers',
        'trip_destinations',
        'trip_guests',
        'trip_segments',
        'trip_lodging',
        'trip_ground_transport',
        'trip_sponsorships',
        'trip_documents',
        'trip_checklists',
        'trip_events',
        'trip_expenses',
        'country_travel_advisories',
        'team_messages',
        'team_message_reactions',
        'team_resources',
        'media_search_terms',
        'press_clips',
        'pitches',
        'inquiries',
        'accomplishments',
        'accomplishment_reactions',
        'regions',
        'countries',
        'us_states',
        'geographables',
        'people_interactions',
        'contact_views',
        'profile_attachments',
        'meeting_attachments',
        'meeting_agenda_items',
        'decisions',
        'commitments',
        'kb_collections',
        // Pivot/link tables
        'meeting_issue',
        'meeting_organization',
        'meeting_person',
        'meeting_project',
        'meeting_user',
        'project_issue',
        'project_organization',
        'project_person',
        'project_staff',
        'project_workspace_notes',
    ];

    public function handle(): int
    {
        $source = (string) $this->option('source');
        $target = (string) $this->option('target');
        $sourceFile = $this->option('source-file');
        $tablesOption = $this->option('tables');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $truncate = (bool) $this->option('truncate');

        if (is_string($sourceFile) && trim($sourceFile) !== '') {
            $sourceFile = trim($sourceFile);
            if (! is_file($sourceFile)) {
                $this->error("Source file does not exist: {$sourceFile}");

                return self::FAILURE;
            }

            config(["database.connections.{$source}.database" => $sourceFile]);
            DB::purge($source);
            $this->line("Using source SQLite file: {$sourceFile}");
        }

        $sourceDriver = DB::connection($source)->getDriverName();
        $targetDriver = DB::connection($target)->getDriverName();

        if ($sourceDriver !== 'sqlite') {
            $this->error("Source connection '{$source}' is '{$sourceDriver}', expected sqlite.");

            return self::FAILURE;
        }

        if ($targetDriver !== 'pgsql') {
            $this->error("Target connection '{$target}' is '{$targetDriver}', expected pgsql.");

            return self::FAILURE;
        }

        $this->info("Source: {$source} ({$sourceDriver})");
        $this->info("Target: {$target} ({$targetDriver})");
        $this->line($dryRun ? 'Mode: dry-run' : 'Mode: write');

        $sourceTablesRaw = collect(Schema::connection($source)->getTableListing())
            ->map(fn ($t) => (string) $t)
            ->values();

        $targetTablesRaw = collect(Schema::connection($target)->getTableListing())
            ->map(fn ($t) => (string) $t)
            ->values();

        $sourceMap = $this->tableMap($sourceTablesRaw);
        $targetMap = $this->tableMap($targetTablesRaw);

        $commonTables = collect(array_keys($sourceMap))
            ->intersect(array_keys($targetMap))
            ->reject(fn (string $table) => in_array($table, $this->skipTables, true))
            ->values();

        if (is_string($tablesOption) && trim($tablesOption) !== '') {
            $requestedTables = collect(explode(',', $tablesOption))
                ->map(fn (string $table) => strtolower(trim($table)))
                ->filter()
                ->values();

            if ($requestedTables->isNotEmpty()) {
                $missing = $requestedTables->reject(fn (string $table) => $commonTables->contains($table))->values();
                if ($missing->isNotEmpty()) {
                    $this->warn('Requested table(s) not found in both source and target: '.$missing->implode(', '));
                }

                $commonTables = $commonTables
                    ->filter(fn (string $table) => $requestedTables->contains($table))
                    ->values();
            }
        }

        if ($commonTables->isEmpty()) {
            $this->error('No common tables found between source and target.');
            $this->line('Source tables found: '.count($sourceMap));
            $this->line('Target tables found: '.count($targetMap));
            $this->line('Source sample: '.implode(', ', array_slice(array_values($sourceMap), 0, 10)));
            $this->line('Target sample: '.implode(', ', array_slice(array_values($targetMap), 0, 10)));

            return self::FAILURE;
        }

        $orderedTables = $this->orderedTables($commonTables);
        $this->line('Tables to process: '.$orderedTables->count());

        if ($truncate && ! $dryRun) {
            $this->truncateTarget($target, $orderedTables, $targetMap);
        }

        $totalRowsCopied = 0;
        $tablesCopied = 0;

        foreach ($orderedTables as $table) {
            $rowsCopied = $this->copyTable(
                table: $table,
                sourceTable: $sourceMap[$table],
                targetTable: $targetMap[$table],
                source: $source,
                target: $target,
                chunkSize: $chunkSize,
                dryRun: $dryRun
            );

            if ($rowsCopied >= 0) {
                $tablesCopied++;
                $totalRowsCopied += $rowsCopied;
            }
        }

        $this->newLine();
        $this->info("Done. Tables processed: {$tablesCopied}, rows copied: {$totalRowsCopied}");

        return self::SUCCESS;
    }

    protected function orderedTables(Collection $commonTables): Collection
    {
        $orderLookup = array_flip($this->tableOrder);

        return $commonTables->sortBy(function (string $table) use ($orderLookup) {
            return $orderLookup[$table] ?? (10_000 + crc32($table));
        })->values();
    }

    protected function truncateTarget(string $target, Collection $tables, array $targetMap): void
    {
        $this->warn('Truncating target tables...');
        $conn = DB::connection($target);

        // Disable FK checks temporarily for fast clean import.
        $conn->statement('SET session_replication_role = replica;');
        try {
            foreach ($tables->reverse() as $table) {
                $targetTable = $targetMap[$table] ?? $table;
                try {
                    $conn->table($targetTable)->truncate();
                    $this->line("  truncated {$targetTable}");
                } catch (\Throwable $e) {
                    $this->warn("  skipped truncate {$targetTable}: {$e->getMessage()}");
                }
            }
        } finally {
            $conn->statement('SET session_replication_role = DEFAULT;');
        }
    }

    protected function copyTable(
        string $table,
        string $sourceTable,
        string $targetTable,
        string $source,
        string $target,
        int $chunkSize,
        bool $dryRun
    ): int {
        try {
            $sourceConn = DB::connection($source);
            $targetConn = DB::connection($target);
            $count = (int) $sourceConn->table($sourceTable)->count();

            if ($count === 0) {
                $this->line("{$table}: 0 rows (skip)");

                return 0;
            }

            $this->line("{$table}: {$count} rows");
            if ($dryRun) {
                return $count;
            }

            $columns = $this->commonColumns($sourceConn, $targetConn, $sourceTable, $targetTable);
            if (empty($columns)) {
                $this->warn("  no shared columns for {$table}, skipped");

                return 0;
            }

            $copied = 0;
            $offset = 0;
            $failedRows = 0;
            $stringLengthMap = $this->stringLengthMap($targetConn, $targetTable);

            while ($offset < $count) {
                $rows = $sourceConn->table($sourceTable)
                    ->select($columns)
                    ->offset($offset)
                    ->limit($chunkSize)
                    ->get();

                if ($rows->isEmpty()) {
                    break;
                }

                $payload = $rows->map(function ($row) {
                    return (array) $row;
                })->all();

                $result = $this->insertChunkWithFallback(
                    $targetConn,
                    $targetTable,
                    $payload,
                    $table,
                    $stringLengthMap
                );
                $copied += $result['inserted'];
                $failedRows += $result['failed'];
                $offset += $chunkSize;
            }

            $this->line("  copied {$copied}");
            if ($failedRows > 0) {
                $this->warn("  skipped {$failedRows} rows in {$table} due to insert errors");
            }

            return $copied;
        } catch (QueryException $e) {
            $this->error("  failed {$table}: {$e->getMessage()}");

            return -1;
        } catch (\Throwable $e) {
            $this->error("  failed {$table}: {$e->getMessage()}");

            return -1;
        }
    }

    protected function commonColumns(
        ConnectionInterface $source,
        ConnectionInterface $target,
        string $sourceTable,
        string $targetTable
    ): array
    {
        $sourceCols = collect(Schema::connection($source->getName())->getColumnListing($sourceTable))
            ->map(fn ($c) => (string) $c);
        $targetCols = collect(Schema::connection($target->getName())->getColumnListing($targetTable))
            ->map(fn ($c) => (string) $c);

        return $sourceCols->intersect($targetCols)->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $payload
     * @return array{inserted: int, failed: int}
     */
    protected function insertChunkWithFallback(
        ConnectionInterface $targetConn,
        string $targetTable,
        array $payload,
        string $table,
        array $stringLengthMap
    ): array {
        if (empty($payload)) {
            return ['inserted' => 0, 'failed' => 0];
        }

        $sanitizedPayload = array_map(function (array $row) use ($stringLengthMap) {
            return $this->sanitizeRow($row, $stringLengthMap);
        }, $payload);

        try {
            $targetConn->table($targetTable)->insert($sanitizedPayload);

            return ['inserted' => count($sanitizedPayload), 'failed' => 0];
        } catch (QueryException $bulkException) {
            $this->warn("  chunk fallback {$table}: ".$this->summarizeDbError($bulkException->getMessage()));
        }

        $inserted = 0;
        $failed = 0;

        foreach ($sanitizedPayload as $index => $cleanRow) {

            try {
                $targetConn->table($targetTable)->insert([$cleanRow]);
                $inserted++;
            } catch (QueryException $rowException) {
                $failed++;
                $originalRow = $payload[$index] ?? [];
                $identifier = $originalRow['id'] ?? '[no-id]';
                $this->warn("    skipped {$table} row {$identifier}: ".$this->summarizeDbError($rowException->getMessage()));
            }
        }

        return ['inserted' => $inserted, 'failed' => $failed];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function sanitizeRow(array $row, array $stringLengthMap): array
    {
        foreach ($row as $key => $value) {
            $maxLength = $stringLengthMap[$key] ?? null;
            $row[$key] = $this->sanitizeValue($value, $maxLength);
        }

        return $row;
    }

    protected function sanitizeValue(mixed $value, ?int $maxLength = null): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $normalized = $value;
        if ($this->isValidUtf8($value)) {
            $normalized = $value;
        } else {
            $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (is_string($clean) && $this->isValidUtf8($clean)) {
                $normalized = $clean;
            } elseif (function_exists('mb_convert_encoding')) {
                try {
                    $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
                    if (is_string($converted) && $this->isValidUtf8($converted)) {
                        $normalized = $converted;
                    } else {
                        $stripped = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value);
                        $normalized = is_string($stripped) ? $stripped : '';
                    }
                } catch (\Throwable) {
                    $stripped = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value);
                    $normalized = is_string($stripped) ? $stripped : '';
                }
            } else {
                $stripped = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value);
                $normalized = is_string($stripped) ? $stripped : '';
            }
        }

        if ($maxLength !== null && $maxLength > 0) {
            return $this->truncateString($normalized, $maxLength);
        }

        return $normalized;
    }

    protected function isValidUtf8(string $value): bool
    {
        return preg_match('//u', $value) === 1;
    }

    protected function truncateString(string $value, int $maxLength): string
    {
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($value, 'UTF-8') > $maxLength
                ? mb_substr($value, 0, $maxLength, 'UTF-8')
                : $value;
        }

        return strlen($value) > $maxLength
            ? substr($value, 0, $maxLength)
            : $value;
    }

    /**
     * @return array<string, int>
     */
    protected function stringLengthMap(ConnectionInterface $targetConn, string $targetTable): array
    {
        $cacheKey = $targetConn->getName().':'.$targetTable;
        if (isset($this->stringLengthCache[$cacheKey])) {
            return $this->stringLengthCache[$cacheKey];
        }

        [$schema, $table] = $this->parseQualifiedTable($targetTable);
        $rows = $targetConn->select(
            'select column_name, character_maximum_length
             from information_schema.columns
             where table_schema = ? and table_name = ? and character_maximum_length is not null',
            [$schema, $table]
        );

        $map = [];
        foreach ($rows as $row) {
            $columnName = (string) $row->column_name;
            $maxLength = (int) $row->character_maximum_length;
            if ($maxLength > 0) {
                $map[$columnName] = $maxLength;
            }
        }

        $this->stringLengthCache[$cacheKey] = $map;

        return $map;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function parseQualifiedTable(string $targetTable): array
    {
        $parts = explode('.', $targetTable, 2);

        if (count($parts) === 2) {
            return [trim($parts[0], '"'), trim($parts[1], '"')];
        }

        return ['public', trim($targetTable, '"')];
    }

    protected function summarizeDbError(string $message): string
    {
        $firstLine = trim((string) str($message)->before("\n"));

        return (string) str($firstLine)->limit(220, '...');
    }

    /**
     * Build map of normalized table names -> raw connection table names.
     *
     * @return array<string, string>
     */
    protected function tableMap(Collection $rawTables): array
    {
        $map = [];

        foreach ($rawTables as $raw) {
            $name = (string) $raw;
            $normalized = strtolower((string) str($name)->afterLast('.'));
            if (! isset($map[$normalized])) {
                $map[$normalized] = $name;
            }
        }

        return $map;
    }
}

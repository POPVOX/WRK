<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalImportRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CongressionalStaffFeedImporter
{
    /**
     * @return array{manifest:array<string,mixed>,processed:int,created:int,updated:int,dry_run:bool,run_id:?int}
     */
    public function import(
        string $source,
        ?int $limit = null,
        ?string $chamber = null,
        bool $dryRun = false,
        ?string $expectedGzipSha256 = null,
        ?string $runSource = null
    ): array {
        [$path, $temporary] = $this->materializeSource($source);
        $expectedGzipSha256 = strtolower(trim((string) $expectedGzipSha256));
        if ($expectedGzipSha256 !== '') {
            if (preg_match('/^[a-f0-9]{64}$/', $expectedGzipSha256) !== 1) {
                if ($temporary) {
                    @unlink($path);
                }

                throw new RuntimeException('Expected feed SHA-256 must be 64 lowercase hexadecimal characters.');
            }
            $actualGzipSha256 = hash_file('sha256', $path);
            if (! hash_equals($expectedGzipSha256, $actualGzipSha256)) {
                if ($temporary) {
                    @unlink($path);
                }

                throw new RuntimeException('Congressional staff feed checksum did not match its manifest.');
            }
        }
        $handle = @gzopen($path, 'rb');
        if ($handle === false) {
            if ($temporary) {
                @unlink($path);
            }

            throw new RuntimeException('Could not open the congressional staff feed as gzip data.');
        }

        $manifest = [];
        $run = null;
        $processed = 0;
        $created = 0;
        $updated = 0;
        $lineNumber = 0;
        $chunk = [];
        $limit = $limit !== null ? max(1, $limit) : null;
        $chamber = $chamber ? ucfirst(strtolower(trim($chamber))) : null;
        $chunkSize = max(50, min(2000, (int) config('congressional.import_chunk_size', 500)));

        try {
            while (! gzeof($handle)) {
                $line = gzgets($handle);
                if ($line === false) {
                    break;
                }

                $lineNumber++;
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $record = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                if ($manifest === []) {
                    $manifest = $this->validateManifest($record, $lineNumber);
                    if (! $dryRun) {
                        $run = CongressionalImportRun::query()->create([
                            'source' => $runSource ?: $source,
                            'schema_version' => (int) $manifest['schemaVersion'],
                            'status' => 'running',
                            'manifest' => $manifest,
                            'started_at' => now(),
                        ]);
                    }

                    continue;
                }

                if (($record['recordType'] ?? null) !== 'observation') {
                    throw new RuntimeException("Unexpected record type on feed line {$lineNumber}.");
                }

                $recordChamber = ucfirst(strtolower(trim((string) ($record['chamber'] ?? ''))));
                if ($chamber && $recordChamber !== $chamber) {
                    continue;
                }

                $normalized = $this->normalizeObservation($record, $lineNumber);
                $processed++;

                if (! $dryRun) {
                    $chunk[] = $normalized;
                    if (count($chunk) >= $chunkSize) {
                        [$chunkCreated, $chunkUpdated] = $this->persistChunk($chunk, $run->id);
                        $created += $chunkCreated;
                        $updated += $chunkUpdated;
                        $chunk = [];
                    }
                }

                if ($limit !== null && $processed >= $limit) {
                    break;
                }
            }

            if ($manifest === []) {
                throw new RuntimeException('Congressional staff feed did not contain a manifest record.');
            }

            if (! $dryRun && $chunk !== []) {
                [$chunkCreated, $chunkUpdated] = $this->persistChunk($chunk, $run->id);
                $created += $chunkCreated;
                $updated += $chunkUpdated;
            }

            if ($run) {
                $run->update([
                    'status' => 'completed',
                    'observations_processed' => $processed,
                    'observations_created' => $created,
                    'observations_updated' => $updated,
                    'completed_at' => now(),
                ]);
            }
        } catch (Throwable $exception) {
            if ($run) {
                $run->update([
                    'status' => 'failed',
                    'observations_processed' => $processed,
                    'observations_created' => $created,
                    'observations_updated' => $updated,
                    'error_message' => Str::limit($exception->getMessage(), 5000),
                    'completed_at' => now(),
                ]);
            }

            throw $exception;
        } finally {
            gzclose($handle);
            if ($temporary) {
                @unlink($path);
            }
        }

        return [
            'manifest' => $manifest,
            'processed' => $processed,
            'created' => $created,
            'updated' => $updated,
            'dry_run' => $dryRun,
            'run_id' => $run?->id,
        ];
    }

    /**
     * @param  array<string,mixed>  $record
     * @return array<string,mixed>
     */
    protected function validateManifest(array $record, int $lineNumber): array
    {
        if (($record['recordType'] ?? null) !== 'manifest') {
            throw new RuntimeException("Feed line {$lineNumber} must be the manifest record.");
        }
        if ((int) ($record['schemaVersion'] ?? 0) !== 1) {
            throw new RuntimeException('Unsupported congressional staff feed schema version.');
        }

        return $record;
    }

    /**
     * @param  array<string,mixed>  $record
     * @return array<string,mixed>
     */
    protected function normalizeObservation(array $record, int $lineNumber): array
    {
        $required = ['observationId', 'sourceRecordHash', 'chamber', 'nameRaw', 'officeRaw', 'titleRaw'];
        foreach ($required as $field) {
            if (trim((string) ($record[$field] ?? '')) === '') {
                throw new RuntimeException("Feed line {$lineNumber} is missing {$field}.");
            }
        }

        $observationId = trim((string) $record['observationId']);
        $sourceHash = strtolower(trim((string) $record['sourceRecordHash']));
        if (preg_match('/^(house|senate):[a-f0-9]{64}$/', strtolower($observationId)) !== 1
            || preg_match('/^[a-f0-9]{64}$/', $sourceHash) !== 1) {
            throw new RuntimeException("Feed line {$lineNumber} has an invalid stable identifier.");
        }

        $chamber = ucfirst(strtolower(trim((string) $record['chamber'])));
        if (! in_array($chamber, ['House', 'Senate'], true)) {
            throw new RuntimeException("Feed line {$lineNumber} has an unsupported chamber.");
        }

        $nameRaw = Str::squish((string) $record['nameRaw']);
        $displayName = Str::squish((string) ($record['nameDisplay'] ?? $nameRaw));
        $identityHint = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', (string) ($record['identityHint'] ?? $nameRaw)) ?? '');
        if ($identityHint === '') {
            throw new RuntimeException("Feed line {$lineNumber} has no usable identity hint.");
        }

        $officeRaw = Str::squish((string) $record['officeRaw']);
        $officeCode = trim((string) ($record['officeCode'] ?? '')) ?: null;
        $normalizedOffice = $this->normalizeText($officeRaw);
        $officeKey = hash('sha256', implode("\x1f", [$chamber, $officeCode ?: $normalizedOffice]));
        $profileKey = hash('sha256', implode("\x1f", [$chamber, $identityHint, $officeKey]));
        $titleRaw = Str::squish((string) $record['titleRaw']);
        $normalizedTitle = $this->normalizeText($titleRaw);
        $positionKey = hash('sha256', implode("\x1f", [$profileKey, $officeKey, $normalizedTitle]));

        return [
            'observation_id' => strtolower($observationId),
            'source_record_hash' => $sourceHash,
            'chamber' => $chamber,
            'name_raw' => $nameRaw,
            'display_name' => $displayName,
            'normalized_name' => $this->normalizeText($displayName),
            'identity_hint' => $identityHint,
            'office_raw' => $officeRaw,
            'office_code' => $officeCode,
            'office_type' => trim((string) ($record['officeType'] ?? '')) ?: null,
            'office_key' => $officeKey,
            'normalized_office' => $normalizedOffice,
            'title_raw' => $titleRaw,
            'normalized_title' => $normalizedTitle,
            'profile_key' => $profileKey,
            'position_key' => $positionKey,
            'period_label' => trim((string) ($record['periodLabel'] ?? '')) ?: null,
            'period_start' => $this->nullableDate($record['periodStart'] ?? null),
            'period_end' => $this->nullableDate($record['periodEnd'] ?? null),
            'active' => (bool) ($record['activeInLatestReport'] ?? false),
            'source_data' => (array) ($record['source'] ?? []),
            'evidence' => (array) ($record['evidence'] ?? []),
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $chunk
     * @return array{int,int}
     */
    protected function persistChunk(array $chunk, int $runId): array
    {
        return DB::transaction(function () use ($chunk, $runId) {
            $now = now();
            $officeRows = [];
            $profileRows = [];

            foreach ($chunk as $row) {
                $existingOffice = $officeRows[$row['office_key']] ?? null;
                $officeRows[$row['office_key']] = [
                    'office_key' => $row['office_key'],
                    'chamber' => $row['chamber'],
                    'name' => $row['office_raw'],
                    'normalized_name' => $row['normalized_office'],
                    'office_code' => $row['office_code'],
                    'office_type' => $row['office_type'],
                    'is_active' => $row['active'],
                    'first_seen_at' => $this->earliestDate($existingOffice['first_seen_at'] ?? null, $row['period_start']),
                    'last_seen_at' => $row['period_end'],
                    'metadata' => json_encode(['source' => 'congressional_staff_feed'], JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $existingProfile = $profileRows[$row['profile_key']] ?? null;
                $profileRows[$row['profile_key']] = [
                    'person_id' => null,
                    'profile_key' => $row['profile_key'],
                    'chamber' => $row['chamber'],
                    'display_name' => $row['display_name'],
                    'normalized_name' => $row['normalized_name'],
                    'identity_hint' => $row['identity_hint'],
                    'status' => $row['active'] ? 'reported_active' : 'reported_historical',
                    'review_status' => 'provisional',
                    'first_seen_at' => $this->earliestDate($existingProfile['first_seen_at'] ?? null, $row['period_start']),
                    'last_seen_at' => $row['period_end'],
                    'latest_period_end' => $row['period_end'],
                    'metadata' => json_encode(['provisional_office_key' => $row['office_key']], JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('congressional_offices')->upsert(
                array_values($officeRows),
                ['office_key'],
                ['name', 'normalized_name', 'office_code', 'office_type', 'is_active', 'last_seen_at', 'metadata', 'updated_at']
            );
            DB::table('congressional_staff_profiles')->upsert(
                array_values($profileRows),
                ['profile_key'],
                ['display_name', 'normalized_name', 'identity_hint', 'status', 'last_seen_at', 'latest_period_end', 'metadata', 'updated_at']
            );

            $officeIds = DB::table('congressional_offices')
                ->whereIn('office_key', array_keys($officeRows))
                ->pluck('id', 'office_key')
                ->all();
            $profileIds = DB::table('congressional_staff_profiles')
                ->whereIn('profile_key', array_keys($profileRows))
                ->pluck('id', 'profile_key')
                ->all();

            $positionRows = [];
            foreach ($chunk as $row) {
                $existingPosition = $positionRows[$row['position_key']] ?? null;
                $positionRows[$row['position_key']] = [
                    'profile_id' => $profileIds[$row['profile_key']],
                    'office_id' => $officeIds[$row['office_key']],
                    'position_key' => $row['position_key'],
                    'title' => $row['title_raw'],
                    'normalized_title' => $row['normalized_title'],
                    'first_reported_start' => $this->earliestDate($existingPosition['first_reported_start'] ?? null, $row['period_start']),
                    'last_reported_end' => $row['period_end'],
                    'is_current' => $row['active'],
                    'confidence' => 'reported',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('congressional_positions')->upsert(
                array_values($positionRows),
                ['position_key'],
                ['title', 'normalized_title', 'last_reported_end', 'is_current', 'confidence', 'updated_at']
            );
            $positionIds = DB::table('congressional_positions')
                ->whereIn('position_key', array_keys($positionRows))
                ->pluck('id', 'position_key')
                ->all();

            $observationIds = array_column($chunk, 'observation_id');
            $existing = DB::table('congressional_staff_observations')
                ->whereIn('observation_id', $observationIds)
                ->pluck('observation_id')
                ->all();
            $existingLookup = array_fill_keys($existing, true);
            $observationRows = [];

            foreach ($chunk as $row) {
                $observationRows[] = [
                    'import_run_id' => $runId,
                    'profile_id' => $profileIds[$row['profile_key']],
                    'office_id' => $officeIds[$row['office_key']],
                    'position_id' => $positionIds[$row['position_key']],
                    'observation_id' => $row['observation_id'],
                    'source_record_hash' => $row['source_record_hash'],
                    'chamber' => $row['chamber'],
                    'name_raw' => $row['name_raw'],
                    'identity_hint' => $row['identity_hint'],
                    'office_raw' => $row['office_raw'],
                    'office_code' => $row['office_code'],
                    'office_type' => $row['office_type'],
                    'title_raw' => $row['title_raw'],
                    'period_label' => $row['period_label'],
                    'period_start' => $row['period_start'],
                    'period_end' => $row['period_end'],
                    'active_in_latest_report' => $row['active'],
                    'source_data' => json_encode($row['source_data'], JSON_THROW_ON_ERROR),
                    'evidence' => json_encode($row['evidence'], JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('congressional_staff_observations')->upsert(
                $observationRows,
                ['observation_id'],
                [
                    'import_run_id', 'profile_id', 'office_id', 'position_id', 'source_record_hash',
                    'chamber', 'name_raw', 'identity_hint', 'office_raw', 'office_code', 'office_type',
                    'title_raw', 'period_label', 'period_start', 'period_end', 'active_in_latest_report',
                    'source_data', 'evidence', 'updated_at',
                ]
            );

            $updated = count(array_filter($observationIds, fn ($id) => isset($existingLookup[$id])));

            return [count($observationIds) - $updated, $updated];
        });
    }

    /**
     * @return array{string,bool}
     */
    protected function materializeSource(string $source): array
    {
        $source = trim($source);
        if ($source === '') {
            throw new RuntimeException('Congressional staff feed source is required.');
        }

        if (! Str::startsWith($source, ['http://', 'https://'])) {
            $path = realpath($source);
            if ($path === false || ! is_readable($path)) {
                throw new RuntimeException("Congressional staff feed is not readable: {$source}");
            }

            return [$path, false];
        }

        $path = tempnam(sys_get_temp_dir(), 'congressional-staff-feed-');
        if ($path === false) {
            throw new RuntimeException('Could not allocate a temporary staff feed file.');
        }

        try {
            $response = Http::timeout(180)
                ->connectTimeout(15)
                ->retry(3, 750)
                ->withOptions(['sink' => $path])
                ->get($source);
            $response->throw();
        } catch (Throwable $exception) {
            @unlink($path);
            throw new RuntimeException('Could not download the congressional staff feed: '.$exception->getMessage(), previous: $exception);
        }

        return [$path, true];
    }

    protected function normalizeText(string $value): string
    {
        return Str::lower(Str::squish($value));
    }

    protected function nullableDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            throw new RuntimeException("Invalid congressional staff feed date: {$value}");
        }

        return $value;
    }

    protected function earliestDate(?string $existing, ?string $candidate): ?string
    {
        if (! $existing) {
            return $candidate;
        }
        if (! $candidate) {
            return $existing;
        }

        return $candidate < $existing ? $candidate : $existing;
    }
}

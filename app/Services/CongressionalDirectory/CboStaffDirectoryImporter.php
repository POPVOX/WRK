<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalStaffObservation;
use Carbon\CarbonImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CboStaffDirectoryImporter
{
    public const SOURCE_URL = 'https://www.cbo.gov/about/organization-and-staffing';

    protected const CDX_URL = 'https://web.archive.org/cdx/search/cdx';

    public function __construct(protected CongressionalStaffFeedImporter $feedImporter) {}

    /**
     * @return array{source_url:string,retrieval_url:string,source_kind:string,snapshot_date:string,archive_timestamp:?string,parsed:int,created:int,updated:int,historical:int,dry_run:bool,run_id:?int,profile_ids:array<int,int>}
     */
    public function import(?string $source = null, bool $dryRun = false): array
    {
        $resolved = $this->resolveSource($source);
        $staff = $this->parseStaff($resolved['html']);
        $minimum = max(1, (int) config('congressional.cbo_minimum_staff', 200));

        if (trim((string) $source) === ''
            && $resolved['source_kind'] === 'cbo_live'
            && count($staff) < $minimum) {
            $resolved = $this->fetchLatestArchive();
            $staff = $this->parseStaff($resolved['html']);
        }

        if (count($staff) < $minimum) {
            throw new RuntimeException(sprintf(
                'CBO staffing page yielded only %d staff rows; refusing to import because at least %d were expected.',
                count($staff),
                $minimum
            ));
        }

        if ($dryRun) {
            return $this->result($resolved, count($staff), 0, 0, 0, true, null, []);
        }

        $path = $this->writeFeed($staff, $resolved);

        try {
            $import = $this->feedImporter->import(
                source: $path,
                runSource: $resolved['retrieval_url']
            );
        } finally {
            @unlink($path);
        }

        $runId = (int) $import['run_id'];
        $current = CongressionalStaffObservation::query()
            ->where('import_run_id', $runId)
            ->get(['id', 'profile_id', 'position_id', 'office_id']);
        $profileIds = $current->pluck('profile_id')->unique()->values()->all();
        $positionIds = $current->pluck('position_id')->unique()->values()->all();
        $officeIds = $current->pluck('office_id')->unique()->values()->all();
        $historical = 0;

        DB::transaction(function () use ($current, $profileIds, $positionIds, $officeIds, &$historical): void {
            if ($officeIds === []) {
                throw new RuntimeException('CBO import did not create or resolve an office.');
            }

            DB::table('congressional_positions')
                ->whereIn('office_id', $officeIds)
                ->whereNotIn('id', $positionIds)
                ->update(['is_current' => false, 'updated_at' => now()]);

            $historical = DB::table('congressional_staff_profiles')
                ->whereIn('id', function ($query) use ($officeIds) {
                    $query->select('profile_id')
                        ->from('congressional_positions')
                        ->whereIn('office_id', $officeIds);
                })
                ->whereNotIn('id', $profileIds)
                ->where('status', '!=', 'reported_historical')
                ->update(['status' => 'reported_historical', 'updated_at' => now()]);

            DB::table('congressional_staff_observations')
                ->whereIn('office_id', $officeIds)
                ->whereNotIn('id', $current->pluck('id')->all())
                ->update(['active_in_latest_report' => false, 'updated_at' => now()]);
        });

        return $this->result(
            $resolved,
            count($staff),
            (int) $import['created'],
            (int) $import['updated'],
            $historical,
            false,
            $runId,
            $profileIds
        );
    }

    /**
     * @return array<int,array{name:string,title:string,division:string,unit:?string}>
     */
    public function parseStaff(string $html): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            if (! $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                throw new RuntimeException('CBO staffing page was not valid HTML.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        $titleClass = "contains(concat(' ', normalize-space(@class), ' '), ' views-field-field-staff-title ')";
        $nameClass = "contains(concat(' ', normalize-space(@class), ' '), ' views-field-title ')";
        $tables = $xpath->query("//table[.//td[{$titleClass}] and .//td[{$nameClass}]]");
        $rows = [];

        foreach ($tables ?: [] as $table) {
            if (! $table instanceof DOMElement) {
                continue;
            }

            $divisionNode = $xpath->query('preceding::h3[1]', $table)?->item(0);
            $captionNode = $xpath->query('./caption[1]', $table)?->item(0);
            $division = $this->text($divisionNode?->textContent ?? 'Congressional Budget Office');
            $unit = $this->text($captionNode?->textContent ?? '') ?: null;

            foreach ($xpath->query('.//tr', $table) ?: [] as $row) {
                $titleNode = $xpath->query(".//td[{$titleClass}][1]", $row)?->item(0);
                $nameNode = $xpath->query(".//td[{$nameClass}][1]", $row)?->item(0);
                $name = $this->text($nameNode?->textContent ?? '');
                $title = $this->text($titleNode?->textContent ?? '');

                if ($name === '' || $title === '') {
                    continue;
                }

                $key = Str::upper(Str::ascii($name));
                $rows[$key] = compact('name', 'title', 'division', 'unit');
            }
        }

        return array_values($rows);
    }

    /** @return array{html:string,retrieval_url:string,source_kind:string,snapshot_date:string,archive_timestamp:?string} */
    protected function resolveSource(?string $source): array
    {
        $source = trim((string) $source);
        if ($source !== '') {
            if (! Str::startsWith($source, ['http://', 'https://'])) {
                $path = realpath($source);
                if ($path === false || ! is_readable($path)) {
                    throw new RuntimeException("CBO staffing source is not readable: {$source}");
                }

                return [
                    'html' => (string) file_get_contents($path),
                    'retrieval_url' => $path,
                    'source_kind' => 'local_override',
                    'snapshot_date' => now()->toDateString(),
                    'archive_timestamp' => null,
                ];
            }

            return $this->fetchUrl($source, 'url_override');
        }

        try {
            return $this->fetchUrl(self::SOURCE_URL, 'cbo_live');
        } catch (Throwable) {
            return $this->fetchLatestArchive();
        }
    }

    /** @return array{html:string,retrieval_url:string,source_kind:string,snapshot_date:string,archive_timestamp:?string} */
    protected function fetchUrl(string $url, string $kind): array
    {
        $isLive = $kind === 'cbo_live';
        $response = Http::timeout($isLive ? 12 : 45)
            ->connectTimeout($isLive ? 5 : 10)
            ->retry($isLive ? 1 : 2, 500)
            ->withUserAgent('POPVOX-Foundation-Congressional-Directory/1.0 (+https://www.popvox.org)')
            ->get($url)
            ->throw();

        $archiveTimestamp = null;
        if (preg_match('#web\.archive\.org/web/(\d{14})id_/#', $url, $matches) === 1) {
            $archiveTimestamp = $matches[1];
            $kind = 'internet_archive_override';
        }

        return [
            'html' => $response->body(),
            'retrieval_url' => $url,
            'source_kind' => $kind,
            'snapshot_date' => $archiveTimestamp
                ? CarbonImmutable::createFromFormat('YmdHis', $archiveTimestamp, 'UTC')->toDateString()
                : now()->toDateString(),
            'archive_timestamp' => $archiveTimestamp,
        ];
    }

    /** @return array{html:string,retrieval_url:string,source_kind:string,snapshot_date:string,archive_timestamp:string} */
    protected function fetchLatestArchive(): array
    {
        $query = http_build_query([
            'url' => 'www.cbo.gov/about/organization-and-staffing',
            'output' => 'json',
            'filter' => 'statuscode:200',
            'fl' => 'timestamp,original,statuscode',
            'collapse' => 'digest',
            'from' => now()->subYears(2)->year,
            'limit' => -20,
        ]);
        $rows = Http::timeout(30)->connectTimeout(10)->retry(3, 750)
            ->withUserAgent('POPVOX-Foundation-Congressional-Directory/1.0 (+https://www.popvox.org)')
            ->get(self::CDX_URL.'?'.$query)
            ->throw()
            ->json();

        if (! is_array($rows) || count($rows) < 2) {
            throw new RuntimeException('Internet Archive did not return a usable CBO staffing snapshot.');
        }

        $snapshots = array_values(array_filter(array_slice($rows, 1), fn ($row) => is_array($row) && preg_match('/^\d{14}$/', (string) ($row[0] ?? '')) === 1
        ));
        usort($snapshots, fn (array $left, array $right) => strcmp((string) $right[0], (string) $left[0]));
        $latest = $snapshots[0] ?? null;
        if (! $latest) {
            throw new RuntimeException('Internet Archive did not return a valid CBO staffing timestamp.');
        }

        $timestamp = (string) $latest[0];
        $original = (string) ($latest[1] ?? self::SOURCE_URL);
        $archiveUrl = "https://web.archive.org/web/{$timestamp}id_/{$original}";
        $response = Http::timeout(90)->connectTimeout(15)->retry(3, 1000)
            ->withUserAgent('POPVOX-Foundation-Congressional-Directory/1.0 (+https://www.popvox.org)')
            ->get($archiveUrl)
            ->throw();

        return [
            'html' => $response->body(),
            'retrieval_url' => $archiveUrl,
            'source_kind' => 'internet_archive',
            'snapshot_date' => CarbonImmutable::createFromFormat('YmdHis', $timestamp, 'UTC')->toDateString(),
            'archive_timestamp' => $timestamp,
        ];
    }

    /**
     * @param  array<int,array{name:string,title:string,division:string,unit:?string}>  $staff
     * @param  array{retrieval_url:string,source_kind:string,snapshot_date:string,archive_timestamp:?string}  $resolved
     */
    protected function writeFeed(array $staff, array $resolved): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cbo-staff-feed-');
        if ($path === false) {
            throw new RuntimeException('Could not allocate a temporary CBO staff feed.');
        }

        $handle = gzopen($path, 'wb9');
        if ($handle === false) {
            @unlink($path);
            throw new RuntimeException('Could not create the temporary CBO staff feed.');
        }

        $manifest = [
            'recordType' => 'manifest',
            'schemaVersion' => 1,
            'generatedAt' => now()->toIso8601String(),
            'totals' => ['observations' => count($staff)],
            'source' => ['kind' => 'cbo_organization_staffing', 'sourceId' => self::SOURCE_URL],
        ];
        gzwrite($handle, json_encode($manifest, JSON_THROW_ON_ERROR)."\n");

        foreach ($staff as $row) {
            $identity = Str::upper(preg_replace('/[^A-Z0-9]+/i', '', Str::ascii($row['name'])) ?? '');
            $stable = hash('sha256', self::SOURCE_URL."\x1f".$identity);
            $sourceRecord = [
                'name' => $row['name'],
                'title' => $row['title'],
                'division' => $row['division'],
                'unit' => $row['unit'],
                'snapshotDate' => $resolved['snapshot_date'],
            ];
            $record = [
                'recordType' => 'observation',
                'observationId' => 'house:'.$stable,
                'sourceRecordHash' => hash('sha256', json_encode($sourceRecord, JSON_THROW_ON_ERROR)),
                'chamber' => 'House',
                'nameRaw' => $row['name'],
                'nameDisplay' => $row['name'],
                'identityHint' => $identity,
                'officeRaw' => 'CONGRESSIONAL BUDGET OFFICE',
                'officeCode' => 'CBO',
                'officeType' => 'Institutional',
                'titleRaw' => $row['title'],
                'periodLabel' => 'CBO directory as of '.$resolved['snapshot_date'],
                'periodStart' => null,
                'periodEnd' => $resolved['snapshot_date'],
                'activeInLatestReport' => true,
                'source' => [
                    'kind' => 'cbo_organization_staffing',
                    'sourceId' => self::SOURCE_URL,
                    'retrievalUrl' => $resolved['retrieval_url'],
                    'retrievalKind' => $resolved['source_kind'],
                    'archiveTimestamp' => $resolved['archive_timestamp'],
                ],
                'evidence' => [
                    'division' => $row['division'],
                    'unit' => $row['unit'],
                    'staffingPage' => self::SOURCE_URL,
                ],
            ];
            gzwrite($handle, json_encode($record, JSON_THROW_ON_ERROR)."\n");
        }

        gzclose($handle);

        return $path;
    }

    protected function text(string $value): string
    {
        return Str::squish(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** @param array<string,mixed> $resolved */
    protected function result(array $resolved, int $parsed, int $created, int $updated, int $historical, bool $dryRun, ?int $runId, array $profileIds): array
    {
        return [
            'source_url' => self::SOURCE_URL,
            'retrieval_url' => $resolved['retrieval_url'],
            'source_kind' => $resolved['source_kind'],
            'snapshot_date' => $resolved['snapshot_date'],
            'archive_timestamp' => $resolved['archive_timestamp'],
            'parsed' => $parsed,
            'created' => $created,
            'updated' => $updated,
            'historical' => $historical,
            'dry_run' => $dryRun,
            'run_id' => $runId,
            'profile_ids' => $profileIds,
        ];
    }
}

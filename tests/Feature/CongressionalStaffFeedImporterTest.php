<?php

use App\Models\CongressionalImportRun;
use App\Models\CongressionalOffice;
use App\Models\CongressionalPosition;
use App\Models\CongressionalStaffObservation;
use App\Models\CongressionalStaffProfile;
use App\Models\Person;
use App\Services\CongressionalDirectory\CongressionalStaffFeedImporter;

function congressionalObservation(
    string $id,
    string $chamber,
    string $name,
    string $identityHint,
    string $office,
    ?string $officeCode,
    string $title,
    string $start,
    string $end,
    bool $active
): array {
    return [
        'recordType' => 'observation',
        'observationId' => strtolower($chamber).':'.str_repeat($id, 64),
        'sourceRecordHash' => str_repeat($id, 64),
        'chamber' => $chamber,
        'nameRaw' => $name,
        'nameDisplay' => $name,
        'identityHint' => $identityHint,
        'officeRaw' => $office,
        'officeCode' => $officeCode,
        'officeType' => str_contains($office, 'Committee') ? 'Committee' : 'Member office',
        'titleRaw' => $title,
        'periodLabel' => $start.' to '.$end,
        'periodStart' => $start,
        'periodEnd' => $end,
        'activeInLatestReport' => $active,
        'source' => ['kind' => 'test', 'sourceId' => 'fixture'],
        'evidence' => ['rowCount' => 1],
    ];
}

function writeCongressionalFeedFixture(): string
{
    $path = tempnam(sys_get_temp_dir(), 'congressional-feed-test-');
    $records = [
        [
            'recordType' => 'manifest',
            'schemaVersion' => 1,
            'generatedAt' => '2026-07-15T00:00:00Z',
            'totals' => ['observations' => 4],
        ],
        congressionalObservation('a', 'House', 'Alex Smith', 'ALEXSMITH', 'Office of Representative One', 'H001', 'Legislative Director', '2025-01-01', '2025-03-31', false),
        congressionalObservation('b', 'House', 'Alex Smith', 'ALEXSMITH', 'Office of Representative One', 'H001', 'Legislative Director', '2026-01-01', '2026-03-31', true),
        congressionalObservation('c', 'House', 'Alex Smith', 'ALEXSMITH', 'Committee on Technology', 'HC01', 'Chief Counsel', '2026-01-01', '2026-03-31', true),
        congressionalObservation('d', 'Senate', 'Casey Jones', 'CASEYJONES', 'Senator Example', null, 'Policy Adviser', '2025-10-01', '2026-03-31', true),
    ];

    $handle = gzopen($path, 'wb9');
    foreach ($records as $record) {
        gzwrite($handle, json_encode($record, JSON_THROW_ON_ERROR)."\n");
    }
    gzclose($handle);

    return $path;
}

test('staff observations import idempotently without merging matching names across offices', function () {
    $path = writeCongressionalFeedFixture();

    try {
        $importer = app(CongressionalStaffFeedImporter::class);
        $first = $importer->import($path);

        expect($first['processed'])->toBe(4)
            ->and($first['created'])->toBe(4)
            ->and($first['updated'])->toBe(0)
            ->and(CongressionalOffice::query()->count())->toBe(3)
            ->and(CongressionalStaffProfile::query()->count())->toBe(3)
            ->and(CongressionalStaffProfile::query()->where('identity_hint', 'ALEXSMITH')->count())->toBe(2)
            ->and(CongressionalPosition::query()->count())->toBe(3)
            ->and(CongressionalStaffObservation::query()->count())->toBe(4)
            ->and(Person::query()->count())->toBe(0);

        $memberOfficePosition = CongressionalPosition::query()
            ->whereHas('office', fn ($query) => $query->where('office_code', 'H001'))
            ->firstOrFail();

        expect($memberOfficePosition->first_reported_start->toDateString())->toBe('2025-01-01')
            ->and($memberOfficePosition->last_reported_end->toDateString())->toBe('2026-03-31')
            ->and($memberOfficePosition->is_current)->toBeTrue();

        $second = $importer->import($path);

        expect($second['created'])->toBe(0)
            ->and($second['updated'])->toBe(4)
            ->and(CongressionalStaffObservation::query()->count())->toBe(4)
            ->and(CongressionalImportRun::query()->where('status', 'completed')->count())->toBe(2);
    } finally {
        @unlink($path);
    }
});

test('staff feed rejects a checksum mismatch before writing', function () {
    $path = writeCongressionalFeedFixture();

    try {
        expect(fn () => app(CongressionalStaffFeedImporter::class)->import(
            source: $path,
            expectedGzipSha256: str_repeat('0', 64)
        ))->toThrow(RuntimeException::class, 'checksum did not match')
            ->and(CongressionalImportRun::query()->count())->toBe(0);
    } finally {
        @unlink($path);
    }
});

test('staff feed dry run validates and filters without writing', function () {
    $path = writeCongressionalFeedFixture();

    try {
        $result = app(CongressionalStaffFeedImporter::class)->import(
            source: $path,
            chamber: 'Senate',
            dryRun: true
        );

        expect($result['processed'])->toBe(1)
            ->and($result['dry_run'])->toBeTrue()
            ->and(CongressionalImportRun::query()->count())->toBe(0)
            ->and(CongressionalStaffObservation::query()->count())->toBe(0);
    } finally {
        @unlink($path);
    }
});

test('congressional directory interface is disabled by default', function () {
    expect(config('features.congressional_directory_ui'))->toBeFalse();
});

<?php

use App\Models\CongressionalOffice;
use App\Models\CongressionalPosition;
use App\Models\CongressionalStaffEmail;
use App\Models\CongressionalStaffProfile;
use App\Models\User;
use App\Services\CongressionalDirectory\CboStaffDirectoryImporter;
use App\Services\CongressionalDirectory\CongressionalEmailGuessService;
use Illuminate\Support\Facades\Http;

function cboStaffHtml(array $rows): string
{
    $body = collect($rows)->map(fn (array $row) => sprintf(
        '<tr><td class="views-field views-field-field-staff-title">%s</td><td class="views-field views-field-title">%s</td></tr>',
        htmlspecialchars($row[1], ENT_QUOTES),
        htmlspecialchars($row[0], ENT_QUOTES)
    ))->implode('');

    return '<!doctype html><html><body><h3 id="tad">Tax Analysis Division</h3>'
        .'<table><caption>Revenue Projections Unit</caption><tbody>'.$body.'</tbody></table></body></html>';
}

function writeCboHtmlFixture(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'cbo-staff-test-');
    file_put_contents($path, cboStaffHtml($rows));

    return $path;
}

beforeEach(function () {
    config()->set('congressional.cbo_minimum_staff', 2);
});

test('CBO importer falls back to the latest archived official directory', function () {
    $html = cboStaffHtml([
        ['Molly Saunders-Scott', 'Deputy Director of Tax Analysis'],
        ['Kathleen Burke', 'Analyst'],
    ]);

    Http::fake(function ($request) use ($html) {
        if ($request->url() === CboStaffDirectoryImporter::SOURCE_URL) {
            return Http::response('bot challenge', 403);
        }
        if (str_starts_with($request->url(), 'https://web.archive.org/cdx/search/cdx')) {
            return Http::response([
                ['timestamp', 'original', 'statuscode'],
                ['20260616114417', CboStaffDirectoryImporter::SOURCE_URL, '200'],
            ]);
        }

        return Http::response($html);
    });

    $result = app(CboStaffDirectoryImporter::class)->import(dryRun: true);

    expect($result)->toMatchArray([
        'source_kind' => 'internet_archive',
        'snapshot_date' => '2026-06-16',
        'archive_timestamp' => '20260616114417',
        'parsed' => 2,
        'dry_run' => true,
    ])->and(CongressionalStaffProfile::query()->count())->toBe(0);
});

test('CBO importer derives the evidence date from an explicit archive URL', function () {
    Http::fake([
        'https://web.archive.org/*' => Http::response(cboStaffHtml([
            ['Molly Saunders-Scott', 'Deputy Director of Tax Analysis'],
            ['Kathleen Burke', 'Analyst'],
        ])),
    ]);

    $result = app(CboStaffDirectoryImporter::class)->import(
        'https://web.archive.org/web/20260616114417id_/https://www.cbo.gov/about/organization-and-staffing',
        true
    );

    expect($result)->toMatchArray([
        'source_kind' => 'internet_archive_override',
        'snapshot_date' => '2026-06-16',
        'archive_timestamp' => '20260616114417',
    ]);
});

test('CBO staff import is idempotent, preserves source context, and generates only provisional emails', function () {
    $path = writeCboHtmlFixture([
        ['Molly Saunders-Scott', 'Deputy Director of Tax Analysis'],
        ['Kathleen Burke', 'Analyst'],
        ['Departing Person', 'Analyst'],
    ]);

    try {
        $first = app(CboStaffDirectoryImporter::class)->import($path);
        $user = User::factory()->create();
        $emails = app(CongressionalEmailGuessService::class)->generateForProfileIds(
            $first['profile_ids'],
            $user->id,
            'Official CBO directory test',
            'CBO directory provisional guess.',
            'cbo_import'
        );

        expect($first['parsed'])->toBe(3)
            ->and($first['created'])->toBe(3)
            ->and(CongressionalOffice::query()->sole()->office_code)->toBe('CBO')
            ->and(CongressionalStaffProfile::query()->count())->toBe(3)
            ->and(CongressionalPosition::query()->where('is_current', true)->count())->toBe(3)
            ->and($emails['generated'])->toBe(3)
            ->and(CongressionalStaffEmail::query()->where('source_type', 'guessed')->count())->toBe(3)
            ->and(CongressionalStaffEmail::query()->where('verification_status', 'unverified')->count())->toBe(3)
            ->and(CongressionalStaffEmail::query()->where('email_normalized', 'molly.saunders-scott@cbo.gov')->exists())->toBeTrue()
            ->and(CongressionalStaffEmail::query()->where('email_normalized', 'kathleen.burke@cbo.gov')->exists())->toBeTrue()
            ->and(data_get(CongressionalStaffProfile::query()->where('display_name', 'Kathleen Burke')->firstOrFail()->latestObservation->evidence, 'unit'))
            ->toBe('Revenue Projections Unit');

        $second = app(CboStaffDirectoryImporter::class)->import($path);

        expect($second['created'])->toBe(0)
            ->and($second['updated'])->toBe(3)
            ->and(CongressionalStaffProfile::query()->count())->toBe(3)
            ->and(CongressionalStaffEmail::query()->count())->toBe(3);
    } finally {
        @unlink($path);
    }
});

test('CBO sync marks missing staff historical and fails closed on an incomplete page', function () {
    $firstPath = writeCboHtmlFixture([
        ['Current Person', 'Analyst'],
        ['Departing Person', 'Analyst'],
    ]);
    $secondPath = writeCboHtmlFixture([
        ['Current Person', 'Senior Analyst'],
        ['New Person', 'Analyst'],
    ]);
    $incompletePath = writeCboHtmlFixture([
        ['Only Person', 'Analyst'],
    ]);

    try {
        app(CboStaffDirectoryImporter::class)->import($firstPath);
        $result = app(CboStaffDirectoryImporter::class)->import($secondPath);

        expect($result['historical'])->toBe(1)
            ->and(CongressionalStaffProfile::query()->where('display_name', 'Departing Person')->sole()->status)->toBe('reported_historical')
            ->and(CongressionalStaffProfile::query()->where('display_name', 'Current Person')->sole()->status)->toBe('reported_active')
            ->and(CongressionalPosition::query()->whereHas('profile', fn ($query) => $query->where('display_name', 'Current Person'))->where('is_current', true)->sole()->title)
            ->toBe('Senior Analyst');

        expect(fn () => app(CboStaffDirectoryImporter::class)->import($incompletePath))
            ->toThrow(RuntimeException::class, 'refusing to import')
            ->and(CongressionalStaffProfile::query()->count())->toBe(3);
    } finally {
        @unlink($firstPath);
        @unlink($secondPath);
        @unlink($incompletePath);
    }
});

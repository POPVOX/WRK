<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalOffice;
use App\Models\CongressionalOutreachDraft;
use App\Models\CongressionalStaffEmail;
use App\Models\CongressionalStaffProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CongressionalEmailGuessService
{
    public const HOUSE_PATTERN = '{first}.{last}@mail.house.gov';

    public const SENATE_PATTERN = '{first}_{last}@{senator_last}.senate.gov';

    /** @var array<int,string|null> */
    protected array $senateDomains = [];

    /**
     * @return array{total:int,house:int,senate:int,guessable:int}
     */
    public function estimate(CongressionalOutreachDraft $draft): array
    {
        $base = $draft->recipients()->where('exclusion_reason', 'no_address');
        $house = (clone $base)
            ->whereHas('profile.currentPosition.office', fn ($query) => $query
                ->where('chamber', 'House')
                ->whereLike('name', 'HON.%'))
            ->count();
        $senate = (clone $base)
            ->whereHas('profile.currentPosition.office', fn ($query) => $query
                ->where('chamber', 'Senate')
                ->whereLike('name', 'SENATOR %'))
            ->count();

        return [
            'total' => (clone $base)->count(),
            'house' => $house,
            'senate' => $senate,
            'guessable' => $house + $senate,
        ];
    }

    /**
     * @return array{email:string,chamber:string,method:string,components:array<string,string>}|null
     */
    public function guess(
        CongressionalStaffProfile $profile,
        string $housePattern = self::HOUSE_PATTERN,
        string $senatePattern = self::SENATE_PATTERN,
        bool $allowAllHouseOffices = false
    ): ?array {
        $profile->loadMissing(['currentPosition.office', 'latestPosition.office', 'latestObservation']);
        $name = $this->staffNameParts($profile);
        if (! $name) {
            return null;
        }

        $office = $profile->currentPosition?->office ?: $profile->latestPosition?->office;
        $houseOfficeIsSupported = $office
            && $office->chamber === 'House'
            && preg_match('/^HON\./i', $office->name) === 1;
        if ($profile->chamber === 'House' && ($allowAllHouseOffices || $houseOfficeIsSupported)) {
            $components = ['first' => $name['first'], 'last' => $name['last']];

            return [
                'email' => $this->renderPattern($housePattern, $components),
                'chamber' => 'House',
                'method' => 'house_first_dot_last',
                'components' => $components,
            ];
        }

        if ($profile->chamber === 'Senate' && $office) {
            $senatorLast = $this->senatorDomainName($office);
            if (! $senatorLast) {
                return null;
            }

            $components = [
                'first' => $name['first'],
                'last' => $name['last'],
                'senator_last' => $senatorLast,
            ];

            return [
                'email' => $this->renderPattern($senatePattern, $components),
                'chamber' => 'Senate',
                'method' => 'senate_first_underscore_last',
                'components' => $components,
            ];
        }

        return null;
    }

    /**
     * @return array{total:int,already_addressed:int,candidates:int,guessable:int,house:int,senate:int,unresolved:int,reported_active:int,reported_historical:int}
     */
    public function estimateAllProfiles(
        string $housePattern = self::HOUSE_PATTERN,
        string $senatePattern = self::SENATE_PATTERN
    ): array {
        $stats = [
            'total' => CongressionalStaffProfile::query()->count(),
            'already_addressed' => CongressionalStaffProfile::query()->whereHas('emails')->count(),
            'candidates' => CongressionalStaffProfile::query()->whereDoesntHave('emails')->count(),
            'guessable' => 0,
            'house' => 0,
            'senate' => 0,
            'unresolved' => 0,
            'reported_active' => 0,
            'reported_historical' => 0,
        ];

        CongressionalStaffProfile::query()
            ->whereDoesntHave('emails')
            ->with(['currentPosition.office', 'latestPosition.office', 'latestObservation'])
            ->orderBy('id')
            ->chunkById(500, function ($profiles) use (&$stats, $housePattern, $senatePattern): void {
                foreach ($profiles as $profile) {
                    $guess = $this->guess($profile, $housePattern, $senatePattern, true);
                    if (! $guess) {
                        $stats['unresolved']++;

                        continue;
                    }

                    $stats['guessable']++;
                    $stats[Str::lower($guess['chamber'])]++;
                    $statusKey = $profile->status === 'reported_historical'
                        ? 'reported_historical'
                        : 'reported_active';
                    $stats[$statusKey]++;
                }
            });

        return $stats;
    }

    /**
     * @return array{total:int,already_addressed:int,candidates:int,generated:int,skipped:int,unresolved:int,house:int,senate:int}
     */
    public function generateForAllProfiles(
        int $userId,
        string $instructions,
        string $housePattern = self::HOUSE_PATTERN,
        string $senatePattern = self::SENATE_PATTERN
    ): array {
        $result = [
            'total' => CongressionalStaffProfile::query()->count(),
            'already_addressed' => CongressionalStaffProfile::query()->whereHas('emails')->count(),
            'candidates' => CongressionalStaffProfile::query()->whereDoesntHave('emails')->count(),
            'generated' => 0,
            'skipped' => 0,
            'unresolved' => 0,
            'house' => 0,
            'senate' => 0,
        ];

        CongressionalStaffProfile::query()
            ->whereDoesntHave('emails')
            ->with(['currentPosition.office', 'latestPosition.office', 'latestObservation', 'emails'])
            ->orderBy('id')
            ->chunkById(100, function ($profiles) use (
                &$result,
                $userId,
                $instructions,
                $housePattern,
                $senatePattern
            ): void {
                $batch = $this->generateProfileBatch(
                    $profiles,
                    $userId,
                    $instructions,
                    $housePattern,
                    $senatePattern,
                    'Database-wide provisional guess.',
                    'global',
                    true
                );

                foreach (['generated', 'skipped', 'unresolved', 'house', 'senate'] as $key) {
                    $result[$key] += $batch[$key];
                }
            });

        return $result;
    }

    /**
     * @return array{generated:int,skipped:int}
     */
    public function generateForDraft(
        CongressionalOutreachDraft $draft,
        int $userId,
        string $instructions,
        string $housePattern = self::HOUSE_PATTERN,
        string $senatePattern = self::SENATE_PATTERN
    ): array {
        $generated = 0;
        $skipped = 0;

        $draft->recipients()
            ->where('exclusion_reason', 'no_address')
            ->with([
                'profile.currentPosition.office',
                'profile.latestPosition.office',
                'profile.latestObservation',
                'profile.emails',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($recipients) use (
                &$generated,
                &$skipped,
                $userId,
                $instructions,
                $housePattern,
                $senatePattern
            ): void {
                $profiles = $recipients->pluck('profile')->filter()->values();
                $skipped += $recipients->count() - $profiles->count();
                $batch = $this->generateProfileBatch(
                    $profiles,
                    $userId,
                    $instructions,
                    $housePattern,
                    $senatePattern,
                    'Batch-generated provisional guess.',
                    'draft',
                    false
                );
                $generated += $batch['generated'];
                $skipped += $batch['skipped'] + $batch['unresolved'];
            });

        return compact('generated', 'skipped');
    }

    /**
     * @param  iterable<int,CongressionalStaffProfile>  $profiles
     * @return array{generated:int,skipped:int,unresolved:int,house:int,senate:int}
     */
    protected function generateProfileBatch(
        iterable $profiles,
        int $userId,
        string $instructions,
        string $housePattern,
        string $senatePattern,
        string $sourceLabel,
        string $scope,
        bool $allowAllHouseOffices
    ): array {
        $result = ['generated' => 0, 'skipped' => 0, 'unresolved' => 0, 'house' => 0, 'senate' => 0];
        $emailRows = [];
        $eventNotes = [];
        $now = now();

        foreach ($profiles as $profile) {
            if ($profile->emails->isNotEmpty()) {
                $result['skipped']++;

                continue;
            }

            $guess = $this->guess($profile, $housePattern, $senatePattern, $allowAllHouseOffices);
            if (! $guess) {
                $result['unresolved']++;

                continue;
            }

            $result[Str::lower($guess['chamber'])]++;
            $notes = implode(' ', array_filter([
                $sourceLabel,
                'Pattern: '.($guess['chamber'] === 'House' ? $housePattern : $senatePattern).'.',
                $instructions !== '' ? 'Instructions: '.$instructions : null,
            ]));
            $email = Str::lower($guess['email']);
            $emailRows[] = [
                'profile_id' => $profile->id,
                'email' => $email,
                'email_normalized' => $email,
                'source_type' => 'guessed',
                'verification_status' => 'unverified',
                'is_primary' => false,
                'source_notes' => Str::limit($notes, 4000, ''),
                'metadata' => json_encode(['guess' => [
                    'method' => $guess['method'],
                    'pattern' => $guess['chamber'] === 'House' ? $housePattern : $senatePattern,
                    'components' => $guess['components'],
                    'instructions' => $instructions,
                    'scope' => $scope,
                    'generated_by' => $userId,
                    'generated_at' => $now->toIso8601String(),
                ]], JSON_THROW_ON_ERROR),
                'added_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $eventNotes[$profile->id.'|'.$email] = Str::limit($notes, 4000, '');
        }

        if ($emailRows === []) {
            return $result;
        }

        DB::transaction(function () use ($emailRows, $eventNotes, $userId, $now, &$result): void {
            $inserted = DB::table('congressional_staff_emails')->insertOrIgnore($emailRows);
            $result['generated'] += $inserted;
            $result['skipped'] += count($emailRows) - $inserted;

            $profileIds = array_column($emailRows, 'profile_id');
            $emails = array_column($emailRows, 'email_normalized');
            $staffEmails = CongressionalStaffEmail::query()
                ->whereIn('profile_id', $profileIds)
                ->whereIn('email_normalized', $emails)
                ->get(['id', 'profile_id', 'email_normalized']);

            $events = $staffEmails
                ->filter(fn (CongressionalStaffEmail $staffEmail) => array_key_exists(
                    $staffEmail->profile_id.'|'.$staffEmail->email_normalized,
                    $eventNotes
                ))
                ->map(function (CongressionalStaffEmail $staffEmail) use ($eventNotes, $userId, $now): array {
                    $key = $staffEmail->profile_id.'|'.$staffEmail->email_normalized;

                    return [
                        'staff_email_id' => $staffEmail->id,
                        'user_id' => $userId,
                        'event_key' => hash('sha256', "address-added|{$staffEmail->id}"),
                        'event_type' => 'address_added',
                        'evidence_strength' => 'low',
                        'evidence_excerpt' => $eventNotes[$key] ?? null,
                        'metadata' => json_encode(['source_type' => 'guessed', 'source_url' => null], JSON_THROW_ON_ERROR),
                        'occurred_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all();

            if ($events !== []) {
                DB::table('congressional_staff_email_events')->insertOrIgnore($events);
            }
        });

        return $result;
    }

    /**
     * @param  array<string,string>  $components
     */
    public function renderPattern(string $pattern, array $components): string
    {
        $rendered = Str::lower(strtr(trim($pattern), collect($components)
            ->mapWithKeys(fn (string $value, string $key) => ['{'.$key.'}' => $value])
            ->all()));

        if (str_contains($rendered, '{') || ! filter_var($rendered, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('The email pattern did not produce a valid address.');
        }

        return $rendered;
    }

    /**
     * @return array{first:string,last:string}|null
     */
    protected function staffNameParts(CongressionalStaffProfile $profile): ?array
    {
        $raw = Str::squish((string) ($profile->latestObservation?->name_raw ?: $profile->display_name));
        $sourceKind = (string) data_get($profile->latestObservation?->source_data, 'kind');

        if (str_contains($raw, ',')) {
            [$lastName, $remaining] = array_map('trim', explode(',', $raw, 2));
            $firstName = Str::before($remaining, ' ');

            return $this->cleanNameParts($firstName, $lastName);
        }

        $tokens = preg_split('/\s+/', $raw) ?: [];
        if ($profile->chamber === 'House'
            && str_starts_with($sourceKind, 'house_statement_of_disbursements')
            && count($tokens) >= 2) {
            return $this->cleanNameParts($tokens[1], $tokens[0]);
        }

        if (count($tokens) < 2) {
            return null;
        }

        return $this->cleanNameParts($tokens[0], $this->lastNonSuffixToken($tokens));
    }

    /**
     * @return array{first:string,last:string}|null
     */
    protected function cleanNameParts(string $firstName, string $lastName): ?array
    {
        $first = $this->emailToken($firstName);
        $last = $this->emailToken($lastName);

        return $first !== '' && $last !== '' ? compact('first', 'last') : null;
    }

    protected function senatorDomainName(CongressionalOffice $office): ?string
    {
        if (array_key_exists($office->id, $this->senateDomains)) {
            return $this->senateDomains[$office->id];
        }

        $observedDomain = CongressionalStaffEmail::query()
            ->where('source_type', '!=', 'guessed')
            ->whereLike('email_normalized', '%@%.senate.gov')
            ->whereHas('profile.positions', fn ($query) => $query->where('office_id', $office->id))
            ->pluck('email_normalized')
            ->map(fn (string $email) => Str::after($email, '@'))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        if (is_string($observedDomain) && str_ends_with($observedDomain, '.senate.gov')) {
            return $this->senateDomains[$office->id] = Str::before($observedDomain, '.senate.gov');
        }

        if (preg_match('/^SENATOR\s+(.+)$/i', Str::squish($office->name), $matches) !== 1) {
            return $this->senateDomains[$office->id] = null;
        }

        $tokens = preg_split('/\s+/', $matches[1]) ?: [];
        array_shift($tokens);
        $tokens = array_values(array_filter($tokens, fn (string $token) => ! $this->isInitialOrSuffix($token)));
        $lastName = array_pop($tokens);
        if (! $lastName) {
            return $this->senateDomains[$office->id] = null;
        }

        $compoundMarkers = ['van', 'von', 'de', 'del', 'la', 'cortez'];
        $surnameTokens = [$lastName];
        while ($tokens !== [] && in_array(Str::lower((string) end($tokens)), $compoundMarkers, true)) {
            array_unshift($surnameTokens, (string) array_pop($tokens));
        }

        $domainName = $this->emailToken(implode('', $surnameTokens));

        return $this->senateDomains[$office->id] = ($domainName !== '' ? $domainName : null);
    }

    /** @param array<int,string> $tokens */
    protected function lastNonSuffixToken(array $tokens): string
    {
        while ($tokens !== [] && $this->isSuffix((string) end($tokens))) {
            array_pop($tokens);
        }

        return (string) end($tokens);
    }

    protected function isInitialOrSuffix(string $value): bool
    {
        return preg_match('/^[A-Z]\.?$/i', $value) === 1 || $this->isSuffix($value);
    }

    protected function isSuffix(string $value): bool
    {
        return in_array(Str::upper(trim($value, '., ')), ['JR', 'SR', 'II', 'III', 'IV'], true);
    }

    protected function emailToken(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', Str::lower(Str::ascii($value))) ?? '';
    }
}

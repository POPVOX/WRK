<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalOffice;
use App\Models\CongressionalOutreachDraft;
use App\Models\CongressionalStaffEmail;
use App\Models\CongressionalStaffProfile;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CongressionalEmailGuessService
{
    public const HOUSE_PATTERN = '{first}.{last}@mail.house.gov';

    public const SENATE_PATTERN = '{first}_{last}@{senator_last}.senate.gov';

    /** @var array<int,string|null> */
    protected array $senateDomains = [];

    public function __construct(
        protected CongressionalEmailEvidenceService $evidence
    ) {}

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
        string $senatePattern = self::SENATE_PATTERN
    ): ?array {
        $profile->loadMissing(['currentPosition.office', 'latestObservation']);
        $office = $profile->currentPosition?->office;
        if (! $office) {
            return null;
        }

        $name = $this->staffNameParts($profile);
        if (! $name) {
            return null;
        }

        if ($office->chamber === 'House' && preg_match('/^HON\./i', $office->name) === 1) {
            $components = ['first' => $name['first'], 'last' => $name['last']];

            return [
                'email' => $this->renderPattern($housePattern, $components),
                'chamber' => 'House',
                'method' => 'house_first_dot_last',
                'components' => $components,
            ];
        }

        if ($office->chamber === 'Senate') {
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
            ->with(['profile.currentPosition.office', 'profile.latestObservation', 'profile.emails'])
            ->orderBy('id')
            ->chunkById(100, function ($recipients) use (
                &$generated,
                &$skipped,
                $userId,
                $instructions,
                $housePattern,
                $senatePattern
            ): void {
                foreach ($recipients as $recipient) {
                    $profile = $recipient->profile;
                    if (! $profile || $profile->emails->isNotEmpty()) {
                        $skipped++;

                        continue;
                    }

                    $guess = $this->guess($profile, $housePattern, $senatePattern);
                    if (! $guess) {
                        $skipped++;

                        continue;
                    }

                    $notes = implode(' ', array_filter([
                        'Batch-generated provisional guess.',
                        'Pattern: '.($guess['chamber'] === 'House' ? $housePattern : $senatePattern).'.',
                        $instructions !== '' ? 'Instructions: '.$instructions : null,
                    ]));
                    $staffEmail = $this->evidence->addAddress(
                        $profile,
                        $guess['email'],
                        'guessed',
                        $userId,
                        sourceNotes: Str::limit($notes, 4000, '')
                    );
                    $metadata = $staffEmail->metadata ?? [];
                    $metadata['guess'] = [
                        'method' => $guess['method'],
                        'pattern' => $guess['chamber'] === 'House' ? $housePattern : $senatePattern,
                        'components' => $guess['components'],
                        'instructions' => $instructions,
                        'generated_by' => $userId,
                        'generated_at' => now()->toIso8601String(),
                    ];
                    $staffEmail->update(['metadata' => $metadata]);
                    $generated++;
                }
            });

        return compact('generated', 'skipped');
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
            ->whereHas('profile.currentPosition', fn ($query) => $query->where('office_id', $office->id))
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

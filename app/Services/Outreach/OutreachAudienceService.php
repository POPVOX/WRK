<?php

namespace App\Services\Outreach;

use App\Models\Person;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OutreachAudienceService
{
    /**
     * @return array<int,array{email:string,name:?string,person_id:?int}>
     */
    public function parseManualRecipients(string $input): array
    {
        $rows = preg_split('/\r\n|\r|\n/', trim($input)) ?: [];
        $seen = [];
        $recipients = [];

        foreach ($rows as $row) {
            $row = trim($row);
            if ($row === '') {
                continue;
            }

            $parsed = $this->parseRecipientRow($row);
            if (! $parsed) {
                continue;
            }

            $email = strtolower($parsed['email']);
            if (isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;

            $personId = Person::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->value('id');

            $recipients[] = [
                'email' => $email,
                'name' => $parsed['name'],
                'person_id' => $personId ? (int) $personId : null,
            ];
        }

        return $recipients;
    }

    /**
     * @param  array<int,string>  $statuses
     * @return array<int,array{email:string,name:?string,person_id:?int}>
     */
    public function fromContactStatuses(array $statuses = []): array
    {
        $query = Person::query()
            ->whereNotNull('email')
            ->whereRaw("TRIM(COALESCE(email, '')) <> ''");

        $normalized = array_values(array_filter(array_map(
            static fn ($value) => trim(Str::lower((string) $value)),
            $statuses
        )));

        if ($normalized !== []) {
            $query->whereIn('status', $normalized);
        }

        /** @var Collection<int,Person> $people */
        $people = $query->orderBy('name')->get(['id', 'name', 'email']);

        $recipients = [];
        $seen = [];
        foreach ($people as $person) {
            $email = strtolower(trim((string) $person->email));
            if ($email === '' || isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;

            $recipients[] = [
                'email' => $email,
                'name' => trim((string) $person->name) !== '' ? (string) $person->name : null,
                'person_id' => (int) $person->id,
            ];
        }

        return $recipients;
    }

    /**
     * @return array{name:?string,email:string}|null
     */
    protected function parseRecipientRow(string $row): ?array
    {
        if (preg_match('/^(?P<name>.+?)\s*<(?P<email>[^>]+)>$/', $row, $matches) === 1) {
            $email = filter_var(trim((string) $matches['email']), FILTER_VALIDATE_EMAIL);
            if (! $email) {
                return null;
            }

            $name = trim((string) $matches['name']);

            return [
                'name' => $name !== '' ? $name : null,
                'email' => $email,
            ];
        }

        if (preg_match('/<(?P<email>[^>]+)>/', $row, $matches) === 1) {
            $email = filter_var(trim((string) $matches['email']), FILTER_VALIDATE_EMAIL);
            if (! $email) {
                return null;
            }

            return [
                'name' => null,
                'email' => $email,
            ];
        }

        $email = filter_var($row, FILTER_VALIDATE_EMAIL);
        if (! $email) {
            return null;
        }

        return [
            'name' => null,
            'email' => $email,
        ];
    }
}


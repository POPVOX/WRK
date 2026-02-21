<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripAgentAction;
use App\Models\TripAgentConversation;
use App\Models\TripAgentMessage;
use App\Models\TripLodging;
use App\Models\User;
use App\Support\AI\AnthropicClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TripAgentService
{
    public function ensureConversation(Trip $trip, User $user, ?TripAgentConversation $conversation = null): TripAgentConversation
    {
        if ($conversation && (int) $conversation->trip_id === (int) $trip->id) {
            return $conversation;
        }

        return TripAgentConversation::firstOrCreate(
            ['trip_id' => $trip->id],
            [
                'user_id' => $user->id,
                'title' => "{$trip->name} Agent Thread",
            ]
        );
    }

    /**
     * @return array{conversation:TripAgentConversation,user_message:TripAgentMessage,assistant_message:TripAgentMessage,action:TripAgentAction|null}
     */
    public function proposeChanges(Trip $trip, User $user, string $message, ?TripAgentConversation $conversation = null): array
    {
        $cleanMessage = trim($message);
        if ($cleanMessage === '') {
            throw new \InvalidArgumentException('Trip agent message is required.');
        }

        $conversation = $this->ensureConversation($trip, $user, $conversation);

        $userMessage = $conversation->messages()->create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $cleanMessage,
            'meta' => null,
        ]);

        $proposal = $this->buildProposal($trip, $cleanMessage);
        $changes = is_array($proposal['changes'] ?? null) ? $proposal['changes'] : [];
        $assistantResponse = trim((string) ($proposal['assistant_response'] ?? ''));
        $summary = trim((string) ($proposal['summary'] ?? ''));

        if ($assistantResponse === '') {
            $assistantResponse = empty($changes)
                ? 'I did not detect a clear trip change to apply yet. Share concrete date/hotel updates and I can draft a proposal.'
                : 'I drafted a change proposal. Review it below and approve to apply.';
        }

        $assistantMessage = $conversation->messages()->create([
            'user_id' => null,
            'role' => 'assistant',
            'content' => $assistantResponse,
            'meta' => [
                'proposal_summary' => $summary !== '' ? $summary : null,
                'proposed_change_count' => count($changes),
            ],
        ]);

        $action = null;
        if (! empty($changes)) {
            $action = $conversation->actions()->create([
                'proposed_by_message_id' => $assistantMessage->id,
                'requested_by' => $user->id,
                'status' => 'pending',
                'summary' => $summary !== '' ? $summary : 'Trip updates proposed',
                'payload' => [
                    'changes' => $changes,
                ],
            ]);

            $assistantMessage->update([
                'meta' => array_merge($assistantMessage->meta ?? [], [
                    'action_id' => $action->id,
                ]),
            ]);
        }

        return [
            'conversation' => $conversation,
            'user_message' => $userMessage,
            'assistant_message' => $assistantMessage->fresh(),
            'action' => $action?->fresh(),
        ];
    }

    public function rejectAction(TripAgentAction $action, User $user): TripAgentAction
    {
        if ($action->status !== 'pending') {
            throw new \RuntimeException("Only pending actions can be rejected. Current status: {$action->status}");
        }

        $action->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'error_message' => null,
        ]);

        $action->conversation->messages()->create([
            'role' => 'assistant',
            'content' => 'Proposal rejected. Share updated details and I can draft a new one.',
            'meta' => [
                'action_id' => $action->id,
                'status' => 'rejected',
            ],
        ]);

        return $action->fresh();
    }

    public function applyAction(TripAgentAction $action, User $user): TripAgentAction
    {
        if (! in_array($action->status, ['pending', 'approved'], true)) {
            throw new \RuntimeException("Only pending/approved actions can be applied. Current status: {$action->status}");
        }

        $conversation = $action->conversation()->with('trip')->firstOrFail();
        $trip = $conversation->trip;
        if (! $trip) {
            throw new \RuntimeException('Trip agent action has no associated trip.');
        }

        $changes = is_array($action->payload['changes'] ?? null)
            ? $action->payload['changes']
            : [];

        if (empty($changes)) {
            throw new \RuntimeException('Action has no changes to apply.');
        }

        DB::transaction(function () use ($action, $trip, $conversation, $user, $changes): void {
            $action->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $executionLog = [];

            foreach ($changes as $change) {
                if (! is_array($change)) {
                    continue;
                }

                $type = strtolower((string) ($change['type'] ?? ''));

                if ($type === 'update_trip_dates') {
                    $log = $this->applyTripDateChange($trip, $change);
                    if ($log !== null) {
                        $executionLog[] = $log;
                    }
                    continue;
                }

                if ($type === 'upsert_lodging') {
                    $log = $this->applyLodgingChange($trip, $change);
                    if ($log !== null) {
                        $executionLog[] = $log;
                    }
                    continue;
                }

                $executionLog[] = [
                    'type' => $type !== '' ? $type : 'unknown',
                    'status' => 'ignored',
                    'reason' => 'Unsupported change type for this phase',
                ];
            }

            $action->update([
                'status' => 'applied',
                'executed_by' => $user->id,
                'executed_at' => now(),
                'execution_log' => $executionLog,
                'error_message' => null,
            ]);

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => 'Approved and applied. I updated the trip records.',
                'meta' => [
                    'action_id' => $action->id,
                    'status' => 'applied',
                    'execution_log' => $executionLog,
                ],
            ]);
        });

        return $action->fresh();
    }

    /**
     * @return array{assistant_response:string,summary:string,changes:array<int,array<string,mixed>>}
     */
    protected function buildProposal(Trip $trip, string $message): array
    {
        $aiKey = (string) (config('services.anthropic.api_key') ?: env('ANTHROPIC_API_KEY'));
        if (! config('ai.enabled') || trim($aiKey) === '') {
            return $this->buildHeuristicProposal($trip, $message);
        }

        $tripContext = $this->buildTripContext($trip);

        $systemPrompt = <<<'PROMPT'
You are WRK Trip Agent. Convert user trip updates into executable structured changes.

Rules:
- Output must be valid JSON only.
- Include only fields explicitly stated or strongly implied in the user message.
- Never invent hotel names, dates, cities, or confirmation numbers.
- Supported change types for this phase:
  1) "update_trip_dates"
  2) "upsert_lodging"
- If info is ambiguous, set missing fields to null and explain in assistant_response.
- Keep assistant_response concise and action-oriented.

Return JSON exactly in this shape:
{
  "assistant_response": "short user-facing response",
  "summary": "one-line summary of proposed changes",
  "changes": [
    {
      "type": "update_trip_dates",
      "start_date": "YYYY-MM-DD or null",
      "end_date": "YYYY-MM-DD or null",
      "notes": "optional notes"
    },
    {
      "type": "upsert_lodging",
      "target": "latest|first|new",
      "property_name": "string or null",
      "city": "string or null",
      "country": "2-letter country code or null",
      "address": "string or null",
      "check_in_date": "YYYY-MM-DD or null",
      "check_out_date": "YYYY-MM-DD or null",
      "confirmation_number": "string or null",
      "room_type": "string or null",
      "notes": "string or null"
    }
  ]
}
PROMPT;

        $userPrompt = "Trip context:\n{$tripContext}\n\nUser message:\n{$message}";

        $response = AnthropicClient::send([
            'system' => $systemPrompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
            'max_tokens' => 1200,
        ]);

        $text = (string) data_get($response, 'content.0.text', '');
        $decoded = $this->decodeJsonBlock($text);

        if (! is_array($decoded)) {
            return $this->buildHeuristicProposal($trip, $message);
        }

        return $this->normalizeProposal($decoded);
    }

    /**
     * @return array{assistant_response:string,summary:string,changes:array<int,array<string,mixed>>}
     */
    protected function buildHeuristicProposal(Trip $trip, string $message): array
    {
        $changes = [];
        $normalized = strtolower($message);

        $dates = $this->extractIsoDates($message);
        if (count($dates) >= 2) {
            $changes[] = [
                'type' => 'update_trip_dates',
                'start_date' => $dates[0],
                'end_date' => $dates[1],
                'notes' => 'Parsed from message fallback parser',
            ];
        }

        $mentionsHotel = str_contains($normalized, 'hotel') || str_contains($normalized, 'lodging');
        if ($mentionsHotel) {
            $changes[] = [
                'type' => 'upsert_lodging',
                'target' => 'latest',
                'property_name' => null,
                'city' => null,
                'country' => null,
                'address' => null,
                'check_in_date' => $dates[0] ?? null,
                'check_out_date' => $dates[1] ?? null,
                'confirmation_number' => null,
                'room_type' => null,
                'notes' => 'Detected hotel/lodging update request',
            ];
        }

        if (empty($changes)) {
            return [
                'assistant_response' => 'I can draft updates when you provide concrete dates/hotel details (for example: start date, end date, hotel name, city).',
                'summary' => 'No actionable update detected',
                'changes' => [],
            ];
        }

        return [
            'assistant_response' => 'I drafted a proposal from your update. Please review and approve before I apply it.',
            'summary' => 'Draft trip updates parsed from message',
            'changes' => $changes,
        ];
    }

    protected function buildTripContext(Trip $trip): string
    {
        $trip->loadMissing(['lodging']);

        $lodgingSummary = $trip->lodging
            ->sortBy('check_in_date')
            ->take(5)
            ->map(function (TripLodging $lodging): string {
                return implode(' | ', array_filter([
                    "id={$lodging->id}",
                    "property={$lodging->property_name}",
                    "city={$lodging->city}",
                    "country={$lodging->country}",
                    'check_in='.$lodging->check_in_date?->format('Y-m-d'),
                    'check_out='.$lodging->check_out_date?->format('Y-m-d'),
                ]));
            })
            ->values()
            ->all();

        return implode("\n", array_filter([
            "trip_id={$trip->id}",
            "name={$trip->name}",
            'start_date='.$trip->start_date?->format('Y-m-d'),
            'end_date='.$trip->end_date?->format('Y-m-d'),
            "primary_destination_city={$trip->primary_destination_city}",
            "primary_destination_country={$trip->primary_destination_country}",
            'lodging='.($lodgingSummary === [] ? 'none' : implode('; ', $lodgingSummary)),
        ]));
    }

    /**
     * @param  array<string,mixed>  $proposal
     * @return array{assistant_response:string,summary:string,changes:array<int,array<string,mixed>>}
     */
    protected function normalizeProposal(array $proposal): array
    {
        $assistantResponse = trim((string) ($proposal['assistant_response'] ?? ''));
        $summary = trim((string) ($proposal['summary'] ?? ''));
        $rawChanges = is_array($proposal['changes'] ?? null) ? $proposal['changes'] : [];
        $changes = [];

        foreach ($rawChanges as $change) {
            if (! is_array($change)) {
                continue;
            }

            $rawType = strtolower(trim((string) ($change['type'] ?? '')));
            $type = match ($rawType) {
                'trip_dates', 'update_dates', 'update_trip_dates', 'dates' => 'update_trip_dates',
                'lodging_update', 'hotel_update', 'upsert_lodging', 'lodging' => 'upsert_lodging',
                default => '',
            };

            if ($type === 'update_trip_dates') {
                $startDate = $this->normalizeDateValue($change['start_date'] ?? null);
                $endDate = $this->normalizeDateValue($change['end_date'] ?? null);

                if ($startDate === null && $endDate === null) {
                    continue;
                }

                $changes[] = [
                    'type' => 'update_trip_dates',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'notes' => $this->nullableTrimmedString($change['notes'] ?? null),
                ];
                continue;
            }

            if ($type === 'upsert_lodging') {
                $target = strtolower(trim((string) ($change['target'] ?? 'latest')));
                if (! in_array($target, ['latest', 'first', 'new'], true)) {
                    $target = 'latest';
                }

                $normalized = [
                    'type' => 'upsert_lodging',
                    'target' => $target,
                    'property_name' => $this->nullableTrimmedString($change['property_name'] ?? null),
                    'city' => $this->nullableTrimmedString($change['city'] ?? null),
                    'country' => $this->normalizeCountryValue($change['country'] ?? null),
                    'address' => $this->nullableTrimmedString($change['address'] ?? null),
                    'check_in_date' => $this->normalizeDateValue($change['check_in_date'] ?? null),
                    'check_out_date' => $this->normalizeDateValue($change['check_out_date'] ?? null),
                    'confirmation_number' => $this->nullableTrimmedString($change['confirmation_number'] ?? null),
                    'room_type' => $this->nullableTrimmedString($change['room_type'] ?? null),
                    'notes' => $this->nullableTrimmedString($change['notes'] ?? null),
                ];

                $isEmpty =
                    $normalized['property_name'] === null
                    && $normalized['city'] === null
                    && $normalized['country'] === null
                    && $normalized['address'] === null
                    && $normalized['check_in_date'] === null
                    && $normalized['check_out_date'] === null
                    && $normalized['confirmation_number'] === null
                    && $normalized['room_type'] === null
                    && $normalized['notes'] === null;

                if (! $isEmpty) {
                    $changes[] = $normalized;
                }
            }
        }

        return [
            'assistant_response' => $assistantResponse,
            'summary' => $summary,
            'changes' => $changes,
        ];
    }

    /**
     * @param  array<string,mixed>  $change
     * @return array<string,mixed>|null
     */
    protected function applyTripDateChange(Trip $trip, array $change): ?array
    {
        $startDate = $this->normalizeDateValue($change['start_date'] ?? null);
        $endDate = $this->normalizeDateValue($change['end_date'] ?? null);

        if ($startDate === null && $endDate === null) {
            return null;
        }

        $currentStart = $trip->start_date?->format('Y-m-d');
        $currentEnd = $trip->end_date?->format('Y-m-d');
        $newStart = $startDate ?? $currentStart;
        $newEnd = $endDate ?? $currentEnd;

        if ($newStart !== null && $newEnd !== null && $newEnd < $newStart) {
            throw new \RuntimeException('Trip end date cannot be earlier than start date.');
        }

        $updates = [];
        if ($newStart !== null && $newStart !== $currentStart) {
            $updates['start_date'] = $newStart;
        }
        if ($newEnd !== null && $newEnd !== $currentEnd) {
            $updates['end_date'] = $newEnd;
        }

        if ($updates !== []) {
            $trip->update($updates);
        }

        return [
            'type' => 'update_trip_dates',
            'status' => $updates === [] ? 'no_change' : 'applied',
            'from' => [
                'start_date' => $currentStart,
                'end_date' => $currentEnd,
            ],
            'to' => [
                'start_date' => $trip->fresh()->start_date?->format('Y-m-d'),
                'end_date' => $trip->fresh()->end_date?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * @param  array<string,mixed>  $change
     * @return array<string,mixed>|null
     */
    protected function applyLodgingChange(Trip $trip, array $change): ?array
    {
        $target = strtolower((string) ($change['target'] ?? 'latest'));
        if (! in_array($target, ['latest', 'first', 'new'], true)) {
            $target = 'latest';
        }

        $query = $trip->lodging();
        $lodging = null;

        if ($target === 'first') {
            $lodging = $query->orderBy('check_in_date', 'asc')->first();
        } elseif ($target === 'latest') {
            $lodging = $query->orderBy('check_in_date', 'desc')->first();
        }

        $isCreate = $target === 'new' || $lodging === null;
        if ($isCreate) {
            $lodging = new TripLodging();
            $lodging->trip_id = $trip->id;
        }

        $checkIn = $this->normalizeDateValue($change['check_in_date'] ?? null);
        $checkOut = $this->normalizeDateValue($change['check_out_date'] ?? null);

        if ($checkIn !== null && $checkOut !== null && $checkOut < $checkIn) {
            throw new \RuntimeException('Lodging check-out date cannot be earlier than check-in date.');
        }

        $propertyName = $this->nullableTrimmedString($change['property_name'] ?? null);
        $city = $this->nullableTrimmedString($change['city'] ?? null);
        $country = $this->normalizeCountryValue($change['country'] ?? null);
        $address = $this->nullableTrimmedString($change['address'] ?? null);
        $confirmationNumber = $this->nullableTrimmedString($change['confirmation_number'] ?? null);
        $roomType = $this->nullableTrimmedString($change['room_type'] ?? null);
        $notes = $this->nullableTrimmedString($change['notes'] ?? null);

        if ($propertyName !== null) {
            $lodging->property_name = $propertyName;
        } elseif ($isCreate && empty($lodging->property_name)) {
            $lodging->property_name = 'TBD Hotel';
        }

        if ($city !== null) {
            $lodging->city = $city;
        } elseif ($isCreate && empty($lodging->city)) {
            $lodging->city = $trip->primary_destination_city;
        }

        if ($country !== null) {
            $lodging->country = $country;
        } elseif ($isCreate && empty($lodging->country)) {
            $lodging->country = strtoupper((string) $trip->primary_destination_country);
        }

        if ($address !== null) {
            $lodging->address = $address;
        }
        if ($confirmationNumber !== null) {
            $lodging->confirmation_number = $confirmationNumber;
        }
        if ($roomType !== null) {
            $lodging->room_type = $roomType;
        }
        if ($notes !== null) {
            $lodging->notes = $notes;
        }

        if ($checkIn !== null) {
            $lodging->check_in_date = $checkIn;
        } elseif ($isCreate && empty($lodging->check_in_date)) {
            $lodging->check_in_date = $trip->start_date?->format('Y-m-d');
        }

        if ($checkOut !== null) {
            $lodging->check_out_date = $checkOut;
        } elseif ($isCreate && empty($lodging->check_out_date)) {
            $lodging->check_out_date = $trip->end_date?->format('Y-m-d');
        }

        if (empty($lodging->property_name) || empty($lodging->city) || empty($lodging->country) || empty($lodging->check_in_date) || empty($lodging->check_out_date)) {
            throw new \RuntimeException('Lodging proposal is missing required data (property/city/country/check-in/check-out).');
        }

        $lodging->save();

        return [
            'type' => 'upsert_lodging',
            'status' => 'applied',
            'mode' => $isCreate ? 'created' : 'updated',
            'lodging_id' => $lodging->id,
            'property_name' => $lodging->property_name,
            'city' => $lodging->city,
            'country' => $lodging->country,
            'check_in_date' => $lodging->check_in_date?->format('Y-m-d'),
            'check_out_date' => $lodging->check_out_date?->format('Y-m-d'),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    protected function decodeJsonBlock(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @return array<int,string>
     */
    protected function extractIsoDates(string $text): array
    {
        preg_match_all('/\b(20\d{2}-\d{2}-\d{2})\b/', $text, $matches);
        $dates = array_values(array_unique($matches[1] ?? []));

        return array_values(array_filter($dates, fn (string $date) => $this->normalizeDateValue($date) !== null));
    }

    protected function normalizeDateValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || strtolower($value) === 'null') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeCountryValue(mixed $value): ?string
    {
        $country = $this->nullableTrimmedString($value);
        if ($country === null) {
            return null;
        }

        $country = strtoupper($country);
        if (strlen($country) !== 2) {
            return null;
        }

        return $country;
    }

    protected function nullableTrimmedString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || strtolower($value) === 'null') {
            return null;
        }

        return Str::limit($value, 1000, '');
    }
}

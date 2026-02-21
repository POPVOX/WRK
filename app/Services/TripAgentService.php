<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripAgentAction;
use App\Models\TripAgentConversation;
use App\Models\TripAgentMessage;
use App\Models\TripLodging;
use App\Models\TripSegment;
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
        $changes = $this->enrichChangesFromRawMessage($trip, $user, $cleanMessage, $changes);
        $assistantResponse = trim((string) ($proposal['assistant_response'] ?? ''));
        $summary = trim((string) ($proposal['summary'] ?? ''));

        if (! empty($changes) && ($summary === '' || Str::contains(Str::lower($summary), ['no actionable', 'not detect']))) {
            $summary = $this->buildChangeSummary($changes);
        }

        if (! empty($changes)) {
            $assistantResponse = sprintf('I identified %d update(s) and I am applying them now.', count($changes));
        }

        if ($assistantResponse === '') {
            $assistantResponse = empty($changes)
                ? 'I did not detect a clear trip change to apply yet. Share concrete date or lodging updates and I will apply them.'
                : 'I parsed your update and I am applying the trip changes now.';
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

            try {
                $action = $this->applyAction($action, $user);
            } catch (\Throwable $exception) {
                $action->update([
                    'status' => 'failed',
                    'executed_by' => $user->id,
                    'executed_at' => now(),
                    'error_message' => $exception->getMessage(),
                ]);

                $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => 'I could not apply those changes automatically. Please share clearer details or try again.',
                    'meta' => [
                        'action_id' => $action->id,
                        'status' => 'failed',
                        'error' => $exception->getMessage(),
                    ],
                ]);
            }
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
        $changes = $this->normalizeLodgingTargets($changes);

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

                if ($type === 'import_itinerary_segments') {
                    $log = $this->applyItinerarySegmentsChange($trip, $change, $user);
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

            $summary = trim((string) $action->summary);
            $executionSummary = $this->buildExecutionSummary($executionLog);
            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $executionSummary !== ''
                    ? $executionSummary
                    : ($summary !== '' ? "Applied: {$summary}" : 'Applied your update to the trip records.'),
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
- Do NOT claim flight or itinerary changes unless they appear explicitly in "changes".
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
                'assistant_response' => 'I can apply updates when you provide concrete dates or hotel details (for example: start date, end date, hotel name, city).',
                'summary' => 'No actionable update detected',
                'changes' => [],
            ];
        }

        return [
            'assistant_response' => 'I parsed your update and I am applying it now.',
            'summary' => 'Trip updates parsed from message',
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
     * @param  array<int,array<string,mixed>>  $changes
     * @return array<int,array<string,mixed>>
     */
    protected function enrichChangesFromRawMessage(Trip $trip, User $user, string $message, array $changes): array
    {
        $changes = $this->normalizeLodgingTargets($changes);

        if (! $this->shouldAttemptItineraryParse($message)) {
            return $changes;
        }

        $itineraryChange = $this->buildItinerarySegmentsChange($trip, $user, $message);
        if ($itineraryChange !== null) {
            $changes[] = $itineraryChange;
        }

        return $changes;
    }

    /**
     * @param  array<int,array<string,mixed>>  $changes
     * @return array<int,array<string,mixed>>
     */
    protected function normalizeLodgingTargets(array $changes): array
    {
        $lodgingCount = 0;

        foreach ($changes as $index => $change) {
            if (! is_array($change) || strtolower((string) ($change['type'] ?? '')) !== 'upsert_lodging') {
                continue;
            }

            $target = strtolower((string) ($change['target'] ?? 'latest'));
            if (! in_array($target, ['latest', 'first', 'new'], true)) {
                $target = 'latest';
            }

            if ($target === 'latest' && $lodgingCount > 0) {
                $target = 'new';
            }

            $changes[$index]['target'] = $target;
            $lodgingCount++;
        }

        return $changes;
    }

    protected function shouldAttemptItineraryParse(string $message): bool
    {
        $trimmed = trim($message);
        if (strlen($trimmed) < 120) {
            return false;
        }

        $lower = Str::lower($trimmed);
        if (Str::contains($lower, ['flight', 'flights', 'itinerary', 'departure', 'arrival', 'segment', 'carrier'])) {
            return true;
        }

        preg_match_all('/\b[A-Z]{3}\b/', $trimmed, $matches);
        $airportCodeCount = count(array_unique($matches[0] ?? []));

        return $airportCodeCount >= 2;
    }

    /**
     * @return array<string,mixed>|null
     */
    protected function buildItinerarySegmentsChange(Trip $trip, User $user, string $message): ?array
    {
        $parser = new ItineraryParserService();
        $result = $parser->parseText($message);
        $segments = is_array($result['segments'] ?? null) ? $result['segments'] : [];

        if ($segments === []) {
            return null;
        }

        $assignments = $this->detectMentionedTravelerAssignments($trip, $message);
        if ($assignments === []) {
            $assignments[] = $this->resolveSegmentAssignment($trip, $user);
        }

        $primaryAssignment = $assignments[0];

        return [
            'type' => 'import_itinerary_segments',
            'user_id' => $primaryAssignment['user_id'],
            'trip_guest_id' => $primaryAssignment['trip_guest_id'],
            'assignments' => $assignments,
            'segments' => array_values($segments),
            'source' => 'message_parser',
            'parsing_notes' => $this->nullableTrimmedString($result['parsing_notes'] ?? null),
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
     * @param  array<string,mixed>  $change
     * @return array<string,mixed>|null
     */
    protected function applyItinerarySegmentsChange(Trip $trip, array $change, User $user): ?array
    {
        $segments = is_array($change['segments'] ?? null) ? $change['segments'] : [];
        if ($segments === []) {
            return null;
        }

        $assignments = $this->normalizeAssignmentsForImport($trip, $user, $change);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $assignmentLabels = [];

        foreach ($assignments as $assignment) {
            $assignmentLabels[] = $this->resolveAssignmentLabel($trip, $assignment);

            foreach ($segments as $rawSegment) {
                if (! is_array($rawSegment)) {
                    $skipped++;
                    continue;
                }

                $segment = $this->normalizeSegmentForImport($rawSegment);
                if ($segment === null) {
                    $skipped++;
                    continue;
                }

                $existing = $trip->segments()
                    ->where('user_id', $assignment['user_id'])
                    ->where('trip_guest_id', $assignment['trip_guest_id'])
                    ->where('type', $segment['type'])
                    ->where('departure_location', $segment['departure_location'])
                    ->where('arrival_location', $segment['arrival_location'])
                    ->where('departure_datetime', $segment['departure_datetime'])
                    ->first();

                if ($existing) {
                    $existing->update(array_merge($segment, [
                        'user_id' => $assignment['user_id'],
                        'trip_guest_id' => $assignment['trip_guest_id'],
                        'ai_extracted' => true,
                    ]));
                    $updated++;
                    continue;
                }

                $trip->segments()->create(array_merge($segment, [
                    'user_id' => $assignment['user_id'],
                    'trip_guest_id' => $assignment['trip_guest_id'],
                    'ai_extracted' => true,
                ]));
                $created++;
            }
        }

        return [
            'type' => 'import_itinerary_segments',
            'status' => ($created + $updated) > 0 ? 'applied' : 'no_change',
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'assignment_count' => count($assignments),
            'assignment_labels' => array_values(array_unique(array_filter($assignmentLabels))),
        ];
    }

    /**
     * @param  array<string,mixed>  $segment
     * @return array<string,mixed>|null
     */
    protected function normalizeSegmentForImport(array $segment): ?array
    {
        $departureLocation = strtoupper(trim((string) ($segment['departure_location'] ?? '')));
        $arrivalLocation = strtoupper(trim((string) ($segment['arrival_location'] ?? '')));

        if ($departureLocation === '' || $arrivalLocation === '') {
            return null;
        }

        try {
            $departureDatetime = Carbon::parse((string) ($segment['departure_datetime'] ?? ''))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }

        $arrivalDatetime = null;
        if (! empty($segment['arrival_datetime'])) {
            try {
                $arrivalDatetime = Carbon::parse((string) $segment['arrival_datetime'])->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                $arrivalDatetime = null;
            }
        }

        $type = strtolower(trim((string) ($segment['type'] ?? 'other_transport')));
        if (! in_array($type, array_keys(TripSegment::getTypeOptions()), true)) {
            $type = 'other_transport';
        }

        $cabinClass = $this->nullableTrimmedString($segment['cabin_class'] ?? null);
        if ($cabinClass !== null && ! in_array($cabinClass, array_keys(TripSegment::getCabinClassOptions()), true)) {
            $cabinClass = null;
        }

        $cost = null;
        if (isset($segment['cost']) && is_numeric($segment['cost'])) {
            $cost = (float) $segment['cost'];
        }

        $currency = strtoupper((string) ($segment['currency'] ?? 'USD'));
        if (strlen($currency) !== 3) {
            $currency = 'USD';
        }

        return [
            'type' => $type,
            'carrier' => $this->nullableTrimmedString($segment['carrier'] ?? null),
            'carrier_code' => $this->nullableTrimmedString($segment['carrier_code'] ?? null),
            'segment_number' => $this->nullableTrimmedString($segment['segment_number'] ?? null),
            'confirmation_number' => $this->nullableTrimmedString($segment['confirmation_number'] ?? null),
            'departure_location' => $departureLocation,
            'departure_city' => $this->nullableTrimmedString($segment['departure_city'] ?? null),
            'departure_datetime' => $departureDatetime,
            'departure_terminal' => $this->nullableTrimmedString($segment['departure_terminal'] ?? null),
            'arrival_location' => $arrivalLocation,
            'arrival_city' => $this->nullableTrimmedString($segment['arrival_city'] ?? null),
            'arrival_datetime' => $arrivalDatetime,
            'arrival_terminal' => $this->nullableTrimmedString($segment['arrival_terminal'] ?? null),
            'seat_assignment' => $this->nullableTrimmedString($segment['seat_assignment'] ?? null),
            'cabin_class' => $cabinClass,
            'cost' => $cost,
            'currency' => $currency,
            'notes' => $this->nullableTrimmedString($segment['notes'] ?? null),
            'ai_confidence' => isset($segment['confidence']) && is_numeric($segment['confidence'])
                ? round((float) $segment['confidence'], 2)
                : null,
        ];
    }

    /**
     * @return array<int,array{user_id:int|null,trip_guest_id:int|null}>
     */
    protected function detectMentionedTravelerAssignments(Trip $trip, string $message): array
    {
        $trip->loadMissing(['travelers', 'guests']);

        $found = [];

        foreach ($trip->travelers as $traveler) {
            $tokens = $this->buildTravelerSearchTokens((string) $traveler->name);
            foreach ($tokens as $token) {
                if ($this->messageContainsTravelerToken($message, $token)) {
                    $found["user:{$traveler->id}"] = [
                        'user_id' => $traveler->id,
                        'trip_guest_id' => null,
                    ];
                    break;
                }
            }
        }

        foreach ($trip->guests as $guest) {
            $tokens = $this->buildTravelerSearchTokens((string) $guest->name);
            foreach ($tokens as $token) {
                if ($this->messageContainsTravelerToken($message, $token)) {
                    $found["guest:{$guest->id}"] = [
                        'user_id' => null,
                        'trip_guest_id' => $guest->id,
                    ];
                    break;
                }
            }
        }

        return array_values($found);
    }

    /**
     * @param  array<string,mixed>  $change
     * @return array<int,array{user_id:int|null,trip_guest_id:int|null}>
     */
    protected function normalizeAssignmentsForImport(Trip $trip, User $user, array $change): array
    {
        $resolved = [];
        $candidates = is_array($change['assignments'] ?? null) ? $change['assignments'] : [];

        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }

            $assignment = $this->resolveSegmentAssignment(
                $trip,
                $user,
                isset($candidate['user_id']) && is_numeric($candidate['user_id']) ? (int) $candidate['user_id'] : null,
                isset($candidate['trip_guest_id']) && is_numeric($candidate['trip_guest_id']) ? (int) $candidate['trip_guest_id'] : null
            );

            $resolved[$this->assignmentKey($assignment)] = $assignment;
        }

        if ($resolved !== []) {
            return array_values($resolved);
        }

        $fallback = $this->resolveSegmentAssignment(
            $trip,
            $user,
            isset($change['user_id']) && is_numeric($change['user_id']) ? (int) $change['user_id'] : null,
            isset($change['trip_guest_id']) && is_numeric($change['trip_guest_id']) ? (int) $change['trip_guest_id'] : null
        );

        return [$fallback];
    }

    /**
     * @param  array{user_id:int|null,trip_guest_id:int|null}  $assignment
     */
    protected function assignmentKey(array $assignment): string
    {
        return ((string) ($assignment['user_id'] ?? 'null')).':'.((string) ($assignment['trip_guest_id'] ?? 'null'));
    }

    protected function messageContainsTravelerToken(string $message, string $token): bool
    {
        $token = trim(Str::lower($token));
        if ($token === '') {
            return false;
        }

        return (bool) preg_match('/\b'.preg_quote($token, '/').'\b/u', Str::lower($message));
    }

    /**
     * @return array<int,string>
     */
    protected function buildTravelerSearchTokens(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $tokens = [trim($name)];

        if (count($parts) > 0) {
            $tokens[] = $parts[0];
        }

        if (count($parts) > 1) {
            $tokens[] = $parts[count($parts) - 1];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (string $part): string => trim($part),
            $tokens
        ), static fn (string $token): bool => strlen($token) >= 3)));
    }

    /**
     * @param  array{user_id:int|null,trip_guest_id:int|null}  $assignment
     */
    protected function resolveAssignmentLabel(Trip $trip, array $assignment): string
    {
        $trip->loadMissing(['travelers', 'guests']);

        $userId = $assignment['user_id'] ?? null;
        if ($userId !== null) {
            $traveler = $trip->travelers->firstWhere('id', $userId);
            if ($traveler) {
                return (string) $traveler->name;
            }
        }

        $guestId = $assignment['trip_guest_id'] ?? null;
        if ($guestId !== null) {
            $guest = $trip->guests->firstWhere('id', $guestId);
            if ($guest) {
                return (string) $guest->name;
            }
        }

        return 'Unassigned traveler';
    }

    /**
     * @return array{user_id:int|null,trip_guest_id:int|null}
     */
    protected function resolveSegmentAssignment(Trip $trip, User $user, ?int $requestedUserId = null, ?int $requestedGuestId = null): array
    {
        $trip->loadMissing(['travelers', 'guests']);

        if ($requestedUserId !== null && $trip->travelers->contains('id', $requestedUserId)) {
            return ['user_id' => $requestedUserId, 'trip_guest_id' => null];
        }

        if ($requestedGuestId !== null && $trip->guests->contains('id', $requestedGuestId)) {
            return ['user_id' => null, 'trip_guest_id' => $requestedGuestId];
        }

        if ($trip->travelers->contains('id', $user->id)) {
            return ['user_id' => $user->id, 'trip_guest_id' => null];
        }

        $leadTraveler = $trip->travelers()->wherePivot('role', 'lead')->first();
        if ($leadTraveler) {
            return ['user_id' => $leadTraveler->id, 'trip_guest_id' => null];
        }

        $firstTraveler = $trip->travelers->first();
        if ($firstTraveler) {
            return ['user_id' => $firstTraveler->id, 'trip_guest_id' => null];
        }

        $firstGuest = $trip->guests->first();
        if ($firstGuest) {
            return ['user_id' => null, 'trip_guest_id' => $firstGuest->id];
        }

        return ['user_id' => null, 'trip_guest_id' => null];
    }

    /**
     * @param  array<int,array<string,mixed>>  $executionLog
     */
    protected function buildExecutionSummary(array $executionLog): string
    {
        $parts = [];
        $ignored = 0;

        foreach ($executionLog as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $type = (string) ($entry['type'] ?? '');
            $status = (string) ($entry['status'] ?? '');

            if ($status === 'ignored') {
                $ignored++;
                continue;
            }

            if ($type === 'update_trip_dates') {
                if ($status === 'applied') {
                    $to = is_array($entry['to'] ?? null) ? $entry['to'] : [];
                    $start = $to['start_date'] ?? null;
                    $end = $to['end_date'] ?? null;
                    $parts[] = $start && $end
                        ? "Trip dates set to {$start} through {$end}"
                        : 'Trip dates updated';
                }
                continue;
            }

            if ($type === 'upsert_lodging') {
                $mode = (string) ($entry['mode'] ?? 'updated');
                $property = (string) ($entry['property_name'] ?? 'lodging');
                $parts[] = ucfirst($mode)." lodging: {$property}";
                continue;
            }

            if ($type === 'import_itinerary_segments') {
                $created = (int) ($entry['created_count'] ?? 0);
                $updated = (int) ($entry['updated_count'] ?? 0);
                $skipped = (int) ($entry['skipped_count'] ?? 0);
                $assignmentCount = (int) ($entry['assignment_count'] ?? 1);
                $labels = is_array($entry['assignment_labels'] ?? null) ? array_values(array_filter($entry['assignment_labels'])) : [];
                $suffix = $assignmentCount > 1 ? " across {$assignmentCount} travelers" : '';
                if ($labels !== []) {
                    $suffix .= ' ('.implode(', ', array_slice($labels, 0, 4)).')';
                }

                $parts[] = "Imported itinerary segments: {$created} created, {$updated} updated, {$skipped} skipped{$suffix}";
            }
        }

        if ($parts === [] && $ignored > 0) {
            return "I processed your request, but only unsupported updates were found ({$ignored} skipped).";
        }

        if ($parts === []) {
            return 'I processed your request, but no records were changed.';
        }

        $message = 'Applied updates: '.implode('; ', $parts).'.';
        if ($ignored > 0) {
            $message .= " {$ignored} unsupported update(s) were skipped.";
        }

        return $message;
    }

    /**
     * @param  array<int,array<string,mixed>>  $changes
     */
    protected function buildChangeSummary(array $changes): string
    {
        $parts = [];

        $dateUpdates = collect($changes)->where('type', 'update_trip_dates')->count();
        if ($dateUpdates > 0) {
            $parts[] = $dateUpdates === 1 ? 'trip dates' : "{$dateUpdates} date updates";
        }

        $lodgingUpdates = collect($changes)->where('type', 'upsert_lodging')->count();
        if ($lodgingUpdates > 0) {
            $parts[] = $lodgingUpdates === 1 ? 'lodging' : "{$lodgingUpdates} lodging updates";
        }

        $segmentImports = collect($changes)->where('type', 'import_itinerary_segments')->count();
        if ($segmentImports > 0) {
            $parts[] = 'itinerary segment import';
        }

        if ($parts === []) {
            return 'Trip updates parsed from message';
        }

        return 'Apply '.implode(', ', $parts);
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

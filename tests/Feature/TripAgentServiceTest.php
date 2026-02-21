<?php

use App\Models\Trip;
use App\Models\TripAgentAction;
use App\Models\TripAgentConversation;
use App\Models\TripAgentMessage;
use App\Models\TripLodging;
use App\Models\TripSegment;
use App\Models\User;
use App\Services\TripAgentService;
use Illuminate\Support\Facades\Http;

function createTripForAgentTest(User $creator): Trip
{
    return Trip::create([
        'name' => 'Brussels Delegation',
        'description' => 'Test trip',
        'type' => 'other',
        'status' => 'planning',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-05',
        'primary_destination_city' => 'Brussels',
        'primary_destination_country' => 'BE',
        'created_by' => $creator->id,
    ]);
}

test('trip agent applies action immediately from ai response', function () {
    config()->set('services.anthropic.api_key', 'test-anthropic-key');
    config()->set('ai.enabled', true);

    Http::fake([
        'https://api.anthropic.com/v1/messages' => Http::response([
            'content' => [
                [
                    'text' => json_encode([
                        'assistant_response' => 'I drafted an update for dates and hotel.',
                        'summary' => 'Shift dates and update hotel details',
                        'changes' => [
                            [
                                'type' => 'update_trip_dates',
                                'start_date' => '2026-03-10',
                                'end_date' => '2026-03-14',
                            ],
                            [
                                'type' => 'upsert_lodging',
                                'target' => 'latest',
                                'property_name' => 'Hilton Brussels Grand Place',
                                'city' => 'Brussels',
                                'country' => 'BE',
                                'check_in_date' => '2026-03-10',
                                'check_out_date' => '2026-03-14',
                            ],
                        ],
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->admin()->create();
    $trip = createTripForAgentTest($user);

    $service = app(TripAgentService::class);
    $result = $service->proposeChanges(
        $trip,
        $user,
        'Please move this trip to March 10-14 and switch hotel to Hilton Brussels Grand Place.'
    );

    $conversation = $result['conversation'];
    $action = $result['action'];

    expect($conversation)->toBeInstanceOf(TripAgentConversation::class);
    expect($action)->toBeInstanceOf(TripAgentAction::class);
    expect($action->status)->toBe('applied');
    expect($action->executed_by)->toBe($user->id);

    $this->assertDatabaseHas('trip_agent_messages', [
        'conversation_id' => $conversation->id,
        'role' => 'user',
    ]);
    $this->assertDatabaseHas('trip_agent_messages', [
        'conversation_id' => $conversation->id,
        'role' => 'assistant',
    ]);
    $this->assertDatabaseHas('trip_agent_actions', [
        'id' => $action->id,
        'conversation_id' => $conversation->id,
        'status' => 'applied',
    ]);
});

test('trip agent answers informational questions without creating actions', function () {
    config()->set('services.anthropic.api_key', 'test-anthropic-key');
    config()->set('ai.enabled', true);

    Http::fake([
        'https://api.anthropic.com/v1/messages' => Http::response([
            'content' => [
                [
                    'text' => json_encode([
                        'assistant_response' => 'This trip is 5 days long and currently has one lodging entry in Brussels.',
                        'summary' => 'Informational response only',
                        'changes' => [],
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->admin()->create();
    $trip = createTripForAgentTest($user);

    $service = app(TripAgentService::class);
    $result = $service->proposeChanges(
        $trip,
        $user,
        'How long is this trip and how many hotels do we currently have?'
    );

    expect($result['action'])->toBeNull();
    expect($result['assistant_message']->content)->toContain('5 days long');
    expect($result['assistant_message']->content)->toContain('lodging entry');
    expect($result['conversation']->actions()->count())->toBe(0);
});

test('trip agent imports itinerary segments from long message text', function () {
    config()->set('services.anthropic.api_key', 'test-anthropic-key');
    config()->set('ai.enabled', true);

    Http::fake([
        'https://api.anthropic.com/v1/messages' => Http::sequence()
            ->push([
                'content' => [
                    [
                        'text' => json_encode([
                            'assistant_response' => 'Processing itinerary details.',
                            'summary' => 'No actionable update detected',
                            'changes' => [],
                        ], JSON_THROW_ON_ERROR),
                    ],
                ],
            ], 200)
            ->push([
                'content' => [
                    [
                        'text' => json_encode([
                            'segments' => [
                                [
                                    'type' => 'flight',
                                    'carrier' => 'American Airlines',
                                    'carrier_code' => 'AA',
                                    'segment_number' => '1679',
                                    'departure_location' => 'DCA',
                                    'arrival_location' => 'MIA',
                                    'departure_datetime' => '2026-02-22T07:00',
                                    'arrival_datetime' => '2026-02-22T10:03',
                                    'seat_assignment' => '27A',
                                    'cabin_class' => 'economy',
                                    'confidence' => 0.93,
                                ],
                                [
                                    'type' => 'flight',
                                    'carrier' => 'American Airlines',
                                    'carrier_code' => 'AA',
                                    'segment_number' => '2742',
                                    'departure_location' => 'UVF',
                                    'arrival_location' => 'MIA',
                                    'departure_datetime' => '2026-02-27T15:48',
                                    'arrival_datetime' => '2026-02-27T18:55',
                                    'seat_assignment' => '22F',
                                    'cabin_class' => 'economy',
                                    'confidence' => 0.91,
                                ],
                            ],
                            'parsing_notes' => 'Parsed two flight segments',
                        ], JSON_THROW_ON_ERROR),
                    ],
                ],
            ], 200),
    ]);

    $user = User::factory()->admin()->create();
    $trip = createTripForAgentTest($user);
    $trip->travelers()->attach($user->id, ['role' => 'lead']);

    $service = app(TripAgentService::class);
    $result = $service->proposeChanges(
        $trip,
        $user,
        "Here are new travel details. FLIGHTS:\nAA1679 DCA to MIA Feb 22 7:00am arrive 10:03am seat 27A.\nAA2742 UVF to MIA Feb 27 3:48pm arrive 6:55pm seat 22F.\nPlease update accordingly."
    );

    $action = $result['action'];
    $trip->refresh();

    expect($action)->not->toBeNull();
    expect($action->status)->toBe('applied');
    expect(TripSegment::where('trip_id', $trip->id)->count())->toBe(2);
    expect(TripSegment::where('trip_id', $trip->id)->where('user_id', $user->id)->count())->toBe(2);

    $executionLog = $action->execution_log ?? [];
    $segmentImportLog = collect($executionLog)->firstWhere('type', 'import_itinerary_segments');
    expect($segmentImportLog)->not->toBeNull();
    expect((int) ($segmentImportLog['created_count'] ?? 0))->toBe(2);
});

test('trip agent splits imported itinerary segments across mentioned travelers', function () {
    config()->set('services.anthropic.api_key', 'test-anthropic-key');
    config()->set('ai.enabled', true);

    Http::fake([
        'https://api.anthropic.com/v1/messages' => Http::sequence()
            ->push([
                'content' => [
                    [
                        'text' => json_encode([
                            'assistant_response' => 'Processing itinerary details.',
                            'summary' => 'No actionable update detected',
                            'changes' => [],
                        ], JSON_THROW_ON_ERROR),
                    ],
                ],
            ], 200)
            ->push([
                'content' => [
                    [
                        'text' => json_encode([
                            'segments' => [
                                [
                                    'type' => 'flight',
                                    'carrier' => 'American Airlines',
                                    'carrier_code' => 'AA',
                                    'segment_number' => '1679',
                                    'departure_location' => 'DCA',
                                    'arrival_location' => 'MIA',
                                    'departure_datetime' => '2026-02-22T07:00',
                                    'arrival_datetime' => '2026-02-22T10:03',
                                    'seat_assignment' => '27A',
                                    'cabin_class' => 'economy',
                                    'confidence' => 0.93,
                                ],
                                [
                                    'type' => 'flight',
                                    'carrier' => 'American Airlines',
                                    'carrier_code' => 'AA',
                                    'segment_number' => '2742',
                                    'departure_location' => 'UVF',
                                    'arrival_location' => 'MIA',
                                    'departure_datetime' => '2026-02-27T15:48',
                                    'arrival_datetime' => '2026-02-27T18:55',
                                    'seat_assignment' => '22F',
                                    'cabin_class' => 'economy',
                                    'confidence' => 0.91,
                                ],
                            ],
                            'parsing_notes' => 'Parsed two flight segments',
                        ], JSON_THROW_ON_ERROR),
                    ],
                ],
            ], 200),
    ]);

    $owner = User::factory()->admin()->create();
    $aubrey = User::factory()->create(['name' => 'Aubrey Wilson']);
    $ben = User::factory()->create(['name' => 'Ben Harris']);
    $bryan = User::factory()->create(['name' => 'Bryan Dease']);

    $trip = createTripForAgentTest($owner);
    $trip->travelers()->attach($aubrey->id, ['role' => 'participant']);
    $trip->travelers()->attach($ben->id, ['role' => 'participant']);
    $trip->travelers()->attach($bryan->id, ['role' => 'participant']);

    $service = app(TripAgentService::class);
    $result = $service->proposeChanges(
        $trip,
        $owner,
        "Please update these flights for Aubrey/Ben/Bryan.\nAA1679 DCA to MIA Feb 22 7:00am arrive 10:03am seat 27A.\nAA2742 UVF to MIA Feb 27 3:48pm arrive 6:55pm seat 22F."
    );

    $action = $result['action'];
    $trip->refresh();

    expect($action)->not->toBeNull();
    expect($action->status)->toBe('applied');
    expect(TripSegment::where('trip_id', $trip->id)->count())->toBe(6);
    expect(TripSegment::where('trip_id', $trip->id)->where('user_id', $aubrey->id)->count())->toBe(2);
    expect(TripSegment::where('trip_id', $trip->id)->where('user_id', $ben->id)->count())->toBe(2);
    expect(TripSegment::where('trip_id', $trip->id)->where('user_id', $bryan->id)->count())->toBe(2);

    $executionLog = $action->execution_log ?? [];
    $segmentImportLog = collect($executionLog)->firstWhere('type', 'import_itinerary_segments');
    expect($segmentImportLog)->not->toBeNull();
    expect((int) ($segmentImportLog['assignment_count'] ?? 0))->toBe(3);
});

test('trip agent applies approved action to trip dates and lodging', function () {
    $user = User::factory()->admin()->create();
    $trip = createTripForAgentTest($user);

    $lodging = TripLodging::create([
        'trip_id' => $trip->id,
        'property_name' => 'Old Hotel',
        'city' => 'Brussels',
        'country' => 'BE',
        'check_in_date' => '2026-03-01',
        'check_out_date' => '2026-03-05',
    ]);

    $conversation = TripAgentConversation::create([
        'trip_id' => $trip->id,
        'user_id' => $user->id,
        'title' => 'Trip Agent Thread',
    ]);

    $assistantMessage = TripAgentMessage::create([
        'conversation_id' => $conversation->id,
        'role' => 'assistant',
        'content' => 'Draft proposal',
    ]);

    $action = TripAgentAction::create([
        'conversation_id' => $conversation->id,
        'proposed_by_message_id' => $assistantMessage->id,
        'requested_by' => $user->id,
        'status' => 'pending',
        'summary' => 'Update dates and lodging',
        'payload' => [
            'changes' => [
                [
                    'type' => 'update_trip_dates',
                    'start_date' => '2026-03-10',
                    'end_date' => '2026-03-14',
                ],
                [
                    'type' => 'upsert_lodging',
                    'target' => 'latest',
                    'property_name' => 'Hilton Brussels Grand Place',
                    'city' => 'Brussels',
                    'country' => 'BE',
                    'check_in_date' => '2026-03-10',
                    'check_out_date' => '2026-03-14',
                    'confirmation_number' => 'ABC12345',
                ],
            ],
        ],
    ]);

    $service = app(TripAgentService::class);
    $service->applyAction($action, $user);

    $trip->refresh();
    $lodging->refresh();
    $action->refresh();

    expect($trip->start_date?->format('Y-m-d'))->toBe('2026-03-10');
    expect($trip->end_date?->format('Y-m-d'))->toBe('2026-03-14');
    expect($lodging->property_name)->toBe('Hilton Brussels Grand Place');
    expect($lodging->check_in_date?->format('Y-m-d'))->toBe('2026-03-10');
    expect($lodging->check_out_date?->format('Y-m-d'))->toBe('2026-03-14');
    expect($action->status)->toBe('applied');
    expect($action->executed_by)->toBe($user->id);
});

test('trip agent does not overwrite multiple lodging updates in one action', function () {
    $user = User::factory()->admin()->create();
    $trip = createTripForAgentTest($user);

    TripLodging::create([
        'trip_id' => $trip->id,
        'property_name' => 'Existing Hotel',
        'city' => 'Brussels',
        'country' => 'BE',
        'check_in_date' => '2026-03-01',
        'check_out_date' => '2026-03-03',
    ]);

    $conversation = TripAgentConversation::create([
        'trip_id' => $trip->id,
        'user_id' => $user->id,
        'title' => 'Trip Agent Thread',
    ]);

    $assistantMessage = TripAgentMessage::create([
        'conversation_id' => $conversation->id,
        'role' => 'assistant',
        'content' => 'Apply lodging updates',
    ]);

    $action = TripAgentAction::create([
        'conversation_id' => $conversation->id,
        'proposed_by_message_id' => $assistantMessage->id,
        'requested_by' => $user->id,
        'status' => 'pending',
        'summary' => 'Two lodging updates',
        'payload' => [
            'changes' => [
                [
                    'type' => 'upsert_lodging',
                    'target' => 'latest',
                    'property_name' => 'Ocean Edge Lodge',
                    'city' => 'Roseau',
                    'country' => 'DM',
                    'check_in_date' => '2026-02-22',
                    'check_out_date' => '2026-02-25',
                ],
                [
                    'type' => 'upsert_lodging',
                    'target' => 'latest',
                    'property_name' => 'Bay Gardens Inn',
                    'city' => 'Gros Islet',
                    'country' => 'LC',
                    'check_in_date' => '2026-02-25',
                    'check_out_date' => '2026-02-27',
                ],
            ],
        ],
    ]);

    $service = app(TripAgentService::class);
    $service->applyAction($action, $user);

    expect(TripLodging::where('trip_id', $trip->id)->where('property_name', 'Ocean Edge Lodge')->exists())->toBeTrue();
    expect(TripLodging::where('trip_id', $trip->id)->where('property_name', 'Bay Gardens Inn')->exists())->toBeTrue();
});

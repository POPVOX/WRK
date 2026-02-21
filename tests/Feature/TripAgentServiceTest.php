<?php

use App\Models\Trip;
use App\Models\TripAgentAction;
use App\Models\TripAgentConversation;
use App\Models\TripAgentMessage;
use App\Models\TripLodging;
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

test('trip agent creates a pending proposal action from ai response', function () {
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
    expect($action->status)->toBe('pending');

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
        'status' => 'pending',
    ]);
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

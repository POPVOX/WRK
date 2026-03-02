<?php

use App\Models\Trip;
use App\Models\User;

function makeTrip(User $creator, array $overrides = []): Trip
{
    return Trip::query()->create(array_merge([
        'name' => 'NCSL 2026',
        'description' => 'Conference trip',
        'type' => 'conference_event',
        'status' => 'planning',
        'start_date' => now()->addDays(7)->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
        'primary_destination_city' => 'Chicago',
        'primary_destination_country' => 'US',
        'created_by' => $creator->id,
    ], $overrides));
}

test('trip upcoming notifications command is idempotent for same day window', function () {
    $traveler = User::factory()->create([
        'is_visible' => true,
        'access_level' => 'staff',
    ]);

    $trip = makeTrip($traveler);
    $trip->travelers()->attach($traveler->id, [
        'role' => 'participant',
    ]);

    $this->artisan('notifications:trip-upcoming --days=7')->assertExitCode(0);
    $this->assertDatabaseCount('notifications', 1);

    $notification = $traveler->notifications()->latest()->first();
    expect($notification)->not->toBeNull();
    expect((string) ($notification->data['kind'] ?? ''))->toBe('trip_upcoming');
    expect((int) ($notification->data['meta']['trip_id'] ?? 0))->toBe($trip->id);
    expect((int) ($notification->data['meta']['days'] ?? 0))->toBe(7);

    $this->artisan('notifications:trip-upcoming --days=7')->assertExitCode(0);
    $this->assertDatabaseCount('notifications', 1);
});

test('trip upcoming notifications command skips hidden travelers', function () {
    $creator = User::factory()->create([
        'is_visible' => true,
        'access_level' => 'management',
    ]);

    $visibleTraveler = User::factory()->create([
        'is_visible' => true,
        'access_level' => 'staff',
    ]);
    $hiddenTraveler = User::factory()->create([
        'is_visible' => false,
        'access_level' => 'staff',
    ]);

    $trip = makeTrip($creator, [
        'name' => 'DC Delegation',
    ]);
    $trip->travelers()->attach($visibleTraveler->id, ['role' => 'lead']);
    $trip->travelers()->attach($hiddenTraveler->id, ['role' => 'participant']);

    $this->artisan('notifications:trip-upcoming --days=7')->assertExitCode(0);

    expect($visibleTraveler->notifications()->count())->toBe(1);
    expect($hiddenTraveler->notifications()->count())->toBe(0);
});

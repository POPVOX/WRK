<?php

use App\Livewire\Dashboard;
use App\Models\Action;
use App\Models\Meeting;
use App\Models\PressClip;
use App\Models\User;
use Livewire\Livewire;

test('morning desk presents live meetings work notes debt coverage and command bar', function () {
    $this->travelTo(now()->setDate(2026, 7, 16)->setTime(9, 0));

    $user = User::factory()->profileCompleted()->create([
        'name' => 'Marci Harris',
        'timezone' => 'America/New_York',
        'location' => 'Washington, DC',
        'timezone_confirmed_at' => now(),
    ]);

    Meeting::query()->create([
        'user_id' => $user->id,
        'title' => 'Partner planning call',
        'meeting_date' => today(),
        'meeting_time' => '15:00',
        'location' => 'Zoom',
        'status' => Meeting::STATUS_NEW,
    ]);

    Meeting::query()->create([
        'user_id' => $user->id,
        'title' => 'Notes debt example',
        'meeting_date' => today()->subDays(2),
        'raw_notes' => null,
        'status' => Meeting::STATUS_PENDING,
    ]);

    Action::query()->create([
        'title' => 'Finish briefing draft',
        'description' => 'Finish briefing draft',
        'assigned_to' => $user->id,
        'status' => Action::STATUS_PENDING,
        'priority' => Action::PRIORITY_HIGH,
        'due_date' => today()->subDay(),
    ]);

    PressClip::query()->create([
        'title' => 'Congressional capacity in the AI era',
        'url' => 'https://example.com/coverage',
        'outlet_name' => 'Federal News Network',
        'published_at' => today(),
        'status' => 'approved',
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('Good morning, Marci.')
        ->assertSee('Partner planning call')
        ->assertSee('Finish briefing draft')
        ->assertSee('1 meeting need notes')
        ->assertSee('Congressional capacity in the AI era')
        ->assertSee('Ask WRK');
});

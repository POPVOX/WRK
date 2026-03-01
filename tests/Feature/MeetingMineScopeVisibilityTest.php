<?php

use App\Models\Meeting;
use App\Models\User;

test('my meetings scope shows meetings owned by current user even without team member pivot', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Meeting::create([
        'user_id' => $user->id,
        'title' => 'Owned Imported Meeting',
        'meeting_date' => now()->addDay()->toDateString(),
        'status' => Meeting::STATUS_NEW,
    ]);

    Meeting::create([
        'user_id' => $otherUser->id,
        'title' => 'Other User Meeting',
        'meeting_date' => now()->addDay()->toDateString(),
        'status' => Meeting::STATUS_NEW,
    ]);

    $this->actingAs($user)
        ->get(route('meetings.index'))
        ->assertOk()
        ->assertSee('Owned Imported Meeting')
        ->assertDontSee('Other User Meeting');
});

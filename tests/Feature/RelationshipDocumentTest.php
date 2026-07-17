<?php

use App\Livewire\Meetings\MeetingDetail;
use App\Livewire\People\PersonShow;
use App\Models\Meeting;
use App\Models\Person;
use App\Models\PersonInteraction;
use App\Models\User;
use Livewire\Livewire;

test('contact records show a unified relationship timeline and ignore future meetings as touches', function () {
    $this->travelTo(now()->setDate(2026, 7, 17)->setTime(9, 0));

    $user = User::factory()->create(['name' => 'Relationship Owner']);
    $person = Person::query()->create([
        'name' => 'Avery Partner',
        'email' => 'avery@example.org',
        'owner_id' => $user->id,
    ]);

    PersonInteraction::query()->create([
        'person_id' => $person->id,
        'user_id' => $user->id,
        'type' => 'call',
        'occurred_at' => now()->subDays(4),
        'summary' => 'Discussed a September workshop.',
    ]);

    $futureMeeting = Meeting::query()->create([
        'user_id' => $user->id,
        'title' => 'September workshop planning',
        'meeting_date' => today()->addMonth(),
    ]);
    $futureMeeting->people()->attach($person);

    Livewire::actingAs($user)
        ->test(PersonShow::class, ['person' => $person])
        ->assertSee('Relationship timeline')
        ->assertSee('Discussed a September workshop.')
        ->assertSee('September workshop planning')
        ->assertSee('4 days ago')
        ->assertDontSee('1 month from now');
});

test('meeting records render imported html notes as readable text', function () {
    $user = User::factory()->create();
    $meeting = Meeting::query()->create([
        'user_id' => $user->id,
        'title' => 'Imported calendar meeting',
        'meeting_date' => today(),
        'raw_notes' => '<p>First line<br/>Second line</p>',
        'meeting_link' => 'https://example.zoom.us/j/123<br/><br/>Meeting agenda',
    ]);

    Livewire::actingAs($user)
        ->test(MeetingDetail::class, ['meeting' => $meeting])
        ->assertSee('First line')
        ->assertSee('Second line')
        ->assertSee('href="https://example.zoom.us/j/123"', false)
        ->assertDontSee('&lt;p&gt;', false)
        ->assertDontSee('&lt;br/&gt;', false)
        ->assertDontSee('href="https://example.zoom.us/j/123&lt;br/', false);
});

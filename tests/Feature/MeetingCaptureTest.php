<?php

use App\Livewire\Meetings\MeetingCapture;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use App\Support\AI\AnthropicClient;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('meeting capture adds and saves organizations and attendees', function () {
    $user = User::factory()->create();
    $existingOrganization = Organization::create(['name' => 'Open Government Network']);

    $component = Livewire::actingAs($user)
        ->test(MeetingCapture::class)
        ->set('newOrganization', '  open government network  ')
        ->call('addOrganization')
        ->assertSet('selectedOrganizations', [$existingOrganization->id])
        ->assertDispatched('notify')
        ->set('newPerson', '  Alex Rivera  ')
        ->call('addPerson')
        ->assertDispatched('notify');

    $person = Person::query()->where('name', 'Alex Rivera')->firstOrFail();
    $component
        ->assertSet('selectedPeople', [$person->id])
        ->set('title', 'Partner follow-up')
        ->set('raw_notes', 'Discussed the next phase of work.')
        ->call('save')
        ->assertRedirect();

    $meeting = Meeting::query()->where('title', 'Partner follow-up')->firstOrFail();
    expect($meeting->organizations()->whereKey($existingOrganization->id)->exists())->toBeTrue();
    expect($meeting->people()->whereKey($person->id)->exists())->toBeTrue();
});

test('meeting capture extracts current notes into reviewable fields and relationships', function () {
    config()->set('services.anthropic.api_key', 'test-anthropic-key');
    config()->set('ai.enabled', true);

    Http::fake([
        AnthropicClient::API_URL => Http::response([
            'content' => [[
                'type' => 'text',
                'text' => json_encode([
                    'suggested_title' => 'Digital Parliament Planning',
                    'organizations' => ['Open Parliament Lab'],
                    'people' => ['Jordan Lee'],
                    'issues' => ['Digital transformation'],
                    'key_ask' => 'Share the implementation brief.',
                    'commitments_made' => 'Send the brief by Friday.',
                    'suggested_date' => '2026-07-09',
                    'ai_summary' => 'The group aligned on the next implementation phase.',
                ]),
            ]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 80],
        ], 200),
    ]);

    $user = User::factory()->create();
    $notes = 'Jordan Lee from Open Parliament Lab asked for the implementation brief.';

    $component = Livewire::actingAs($user)
        ->test(MeetingCapture::class)
        ->set('raw_notes', $notes)
        ->call('extractWithAI')
        ->assertSet('raw_notes', $notes)
        ->assertSet('title', 'Digital Parliament Planning')
        ->assertSet('meeting_date', '2026-07-09')
        ->assertSet('aiSummary', 'The group aligned on the next implementation phase.')
        ->assertSet('keyAsk', 'Share the implementation brief.')
        ->assertSet('commitmentsMade', 'Send the brief by Friday.')
        ->assertSet('isExtracting', false)
        ->assertDispatched('notify');

    $organization = Organization::query()->where('name', 'Open Parliament Lab')->firstOrFail();
    $person = Person::query()->where('name', 'Jordan Lee')->firstOrFail();
    $component->assertSet('selectedOrganizations', [$organization->id]);
    $component->assertSet('selectedPeople', [$person->id]);

    Http::assertSent(fn ($request): bool => $request->url() === AnthropicClient::API_URL
        && $request['model'] === config('ai.model')
        && str_contains($request['messages'][0]['content'], $notes));
});

test('meeting capture keeps notes and reports provider failures truthfully', function () {
    config()->set('services.anthropic.api_key', 'test-anthropic-key');
    Http::fake([
        AnthropicClient::API_URL => Http::response(['error' => ['message' => 'Unavailable']], 503),
    ]);

    $user = User::factory()->create();
    $notes = 'These notes must remain available after an extraction error.';

    Livewire::actingAs($user)
        ->test(MeetingCapture::class)
        ->set('raw_notes', $notes)
        ->call('extractWithAI')
        ->assertSet('raw_notes', $notes)
        ->assertSet('aiSummary', null)
        ->assertSet('isExtracting', false)
        ->assertDispatched('notify', fn (string $name, array $params): bool => $params['type'] === 'error');
});

test('meeting capture uses action-synchronized models for notes and relationships', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(MeetingCapture::class)
        ->assertSee('wire:model="raw_notes"', false)
        ->assertSee('wire:model="newOrganization"', false)
        ->assertSee('wire:model="newPerson"', false)
        ->assertDontSee('wire:model.live.debounce.500ms="raw_notes"', false);
});

<?php

use App\Livewire\Meetings\MeetingCapture;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use App\Support\AI\AnthropicClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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
        ->assertSet('extractionMessageType', 'success')
        ->assertSee('AI-extracted meeting details')
        ->assertSet('isExtracting', false)
        ->assertDispatched('notify');

    $organization = Organization::query()->where('name', 'Open Parliament Lab')->firstOrFail();
    $person = Person::query()->where('name', 'Jordan Lee')->firstOrFail();
    $component->assertSet('selectedOrganizations', [$organization->id]);
    $component->assertSet('selectedPeople', [$person->id]);

    Http::assertSent(fn ($request): bool => $request->url() === AnthropicClient::API_URL
        && $request['model'] === AnthropicClient::defaultModel()
        && str_contains($request['messages'][0]['content'], $notes));
});

test('meeting extraction replaces the retired Sonnet 4 model configured in an environment', function () {
    config()->set('services.anthropic.api_key', 'test-anthropic-key');
    config()->set('ai.enabled', true);
    config()->set('ai.model', 'claude-sonnet-4-20250514');

    Http::fake([
        AnthropicClient::API_URL => Http::response([
            'content' => [['type' => 'text', 'text' => json_encode([
                'suggested_title' => 'Model migration check',
                'organizations' => [],
                'people' => [],
                'issues' => [],
                'key_ask' => '',
                'commitments_made' => '',
                'suggested_date' => null,
                'ai_summary' => 'The supported model completed extraction.',
            ])]],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 40, 'output_tokens' => 30],
        ], 200),
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(MeetingCapture::class)
        ->set('raw_notes', 'Verify that extraction uses a supported model.')
        ->call('extractWithAI')
        ->assertSet('title', 'Model migration check')
        ->assertSet('extractionMessageType', 'success');

    Http::assertSent(fn ($request): bool => $request['model'] === 'claude-sonnet-4-6');
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
        ->assertSet('extractionMessageType', 'error')
        ->assertSee('Anthropic is temporarily unavailable')
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

test('meeting capture queues extraction instead of holding the browser request open', function () {
    Queue::fake();
    $user = User::factory()->create();
    $notes = str_repeat('Detailed meeting note. ', 4000);

    Livewire::actingAs($user)
        ->test(MeetingCapture::class)
        ->set('raw_notes', $notes)
        ->call('extractWithAI')
        ->assertSet('isExtracting', true)
        ->assertSet('extractionMessageType', 'info')
        ->assertSee('AI extraction is running in the background')
        ->assertSee('wire:poll.2s="checkExtractionStatus"', false)
        ->assertSee('Extracting in background...');

    Queue::assertPushed(\App\Jobs\ExtractMeetingNotes::class, fn ($job): bool => $job->userId === $user->id
        && strlen($job->notes) < strlen($notes)
        && str_contains($job->notes, 'Middle of exceptionally long notes omitted'));
    Http::assertNothingSent();
});

test('meeting capture parses JSON from a later AI text block', function () {
    config()->set('services.anthropic.api_key', 'test-anthropic-key');
    config()->set('ai.enabled', true);

    Http::fake([
        AnthropicClient::API_URL => Http::response([
            'content' => [
                ['type' => 'text', 'text' => 'I will structure the result now.'],
                ['type' => 'text', 'text' => json_encode([
                    'suggested_title' => 'Internal planning',
                    'organizations' => [],
                    'people' => [],
                    'issues' => [],
                    'key_ask' => '',
                    'commitments_made' => 'Draft the brief by Friday.',
                    'suggested_date' => null,
                    'ai_summary' => 'The team agreed on the next deliverable.',
                ])],
            ],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 80, 'output_tokens' => 50],
        ], 200),
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(MeetingCapture::class)
        ->set('raw_notes', 'Internal planning notes with a Friday deliverable.')
        ->call('extractWithAI')
        ->assertSet('title', 'Internal planning')
        ->assertSet('aiSummary', 'The team agreed on the next deliverable.')
        ->assertSet('commitmentsMade', 'Draft the brief by Friday.')
        ->assertSet('extractionMessageType', 'success');
});

test('meeting capture reports a truncated AI response instead of appearing successful', function () {
    config()->set('services.anthropic.api_key', 'test-anthropic-key');
    config()->set('ai.enabled', true);

    Http::fake([
        AnthropicClient::API_URL => Http::response([
            'content' => [['type' => 'text', 'text' => '{']],
            'stop_reason' => 'max_tokens',
            'usage' => ['input_tokens' => 2000, 'output_tokens' => 2048],
        ], 200),
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(MeetingCapture::class)
        ->set('raw_notes', str_repeat('Long meeting notes. ', 300))
        ->call('extractWithAI')
        ->assertSet('aiSummary', null)
        ->assertSet('extractionMessageType', 'error')
        ->assertSee('The AI response was incomplete')
        ->assertDispatched('notify', fn (string $name, array $params): bool => $params['type'] === 'error');
});

test('meeting capture explains production billing failures safely', function () {
    config()->set('services.anthropic.api_key', 'test-anthropic-key');
    config()->set('ai.enabled', true);

    Http::fake([
        AnthropicClient::API_URL => Http::response([
            'type' => 'error',
            'error' => [
                'type' => 'invalid_request_error',
                'message' => 'Your credit balance is too low to access the Anthropic API.',
            ],
        ], 400),
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(MeetingCapture::class)
        ->set('raw_notes', 'Notes that should be extracted.')
        ->call('extractWithAI')
        ->assertSet('extractionMessageType', 'error')
        ->assertSee('Anthropic account needs billing attention')
        ->assertDontSee('credit balance is too low')
        ->assertDispatched('notify', fn (string $name, array $params): bool => $params['type'] === 'error');

    expect(cache('metrics:ai:last_error_status'))->toBe(400);
});

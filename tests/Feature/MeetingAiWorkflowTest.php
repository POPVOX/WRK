<?php

use App\Livewire\Meetings\MeetingDetail;
use App\Models\Action;
use App\Models\Meeting;
use App\Models\User;
use App\Support\AI\AnthropicClient;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('refreshing a meeting recap preserves source notes and requires action acceptance', function () {
    config()->set('services.anthropic.api_key', 'test-anthropic-key');
    config()->set('ai.enabled', true);

    Http::fake([
        AnthropicClient::API_URL => Http::response([
            'content' => [['type' => 'text', 'text' => json_encode([
                'suggested_title' => 'Ignored replacement title',
                'organizations' => [],
                'people' => [],
                'issues' => [],
                'key_ask' => 'Share the source document.',
                'commitments_made' => 'Send it next week.',
                'suggested_date' => null,
                'ai_summary' => 'The group requested the source document.',
                'action_items' => [[
                    'description' => 'Send the source document',
                    'owner_name' => 'Alex',
                    'due_date' => null,
                ]],
            ])]],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 50, 'output_tokens' => 40],
        ], 200),
    ]);

    $user = User::factory()->create();
    $notes = 'Rough human notes that must remain unchanged.';
    $meeting = Meeting::query()->create([
        'user_id' => $user->id,
        'title' => 'Source of truth meeting',
        'meeting_date' => today(),
        'raw_notes' => $notes,
        'meeting_type' => Meeting::TYPE_BRIEFING,
    ]);

    $component = Livewire::actingAs($user)
        ->test(MeetingDetail::class, ['meeting' => $meeting])
        ->call('summarizeNotes')
        ->assertSet('raw_notes', $notes)
        ->assertSet('aiSummary', 'The group requested the source document.')
        ->assertCount('suggestedActions', 1);

    $meeting->refresh();
    expect($meeting->raw_notes)->toBe($notes)
        ->and($meeting->actions)->toHaveCount(0)
        ->and($meeting->ai_generated_at)->not->toBeNull();

    $action = $component->get('suggestedActions')[0];
    $component->call('acceptSuggestedAction', $component->instance()->actionSuggestionKey($action));

    expect($meeting->fresh()->actions)->toHaveCount(1)
        ->and($meeting->fresh()->actions->first()->source)->toBe(Action::SOURCE_AI_SUGGESTED);
});

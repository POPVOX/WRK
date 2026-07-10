<?php

use App\Livewire\Admin\AttentionPilotInsights;
use App\Models\AttentionFeedback;
use App\Models\User;
use Livewire\Livewire;

test('attention pilot insights are limited to management', function () {
    $staff = User::factory()->create(['access_level' => 'staff']);
    $manager = User::factory()->create(['access_level' => 'management']);

    $this->actingAs($staff)
        ->get(route('attention.insights'))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('attention.insights'))
        ->assertOk()
        ->assertSee('Attention Pilot Insights');
});

test('attention pilot insights aggregate ratings and missing signals', function () {
    $manager = User::factory()->create(['access_level' => 'management']);
    $staff = User::factory()->create(['name' => 'Pilot Staffer']);

    AttentionFeedback::query()->create([
        'user_id' => $staff->id,
        'item_key' => 'action-10',
        'source_type' => 'action',
        'source_id' => 10,
        'rule_key' => 'action_due_soon',
        'category' => 'tasks',
        'response' => AttentionFeedback::RESPONSE_USEFUL,
    ]);
    AttentionFeedback::query()->create([
        'user_id' => $staff->id,
        'item_key' => 'meeting-prep-20',
        'source_type' => 'meeting',
        'source_id' => 20,
        'rule_key' => 'meeting_prep_missing',
        'category' => 'meetings',
        'response' => AttentionFeedback::RESPONSE_NOT_RELEVANT,
    ]);
    AttentionFeedback::query()->create([
        'user_id' => $staff->id,
        'response' => AttentionFeedback::RESPONSE_MISSING,
        'note' => 'Surface decisions that still need a follow-up owner.',
    ]);

    Livewire::actingAs($manager)
        ->test(AttentionPilotInsights::class)
        ->assertSee('50%')
        ->assertSee('Pilot Staffer')
        ->assertSee('Surface decisions that still need a follow-up owner.')
        ->assertSee('Assigned action due soon')
        ->assertSee('Upcoming meeting missing preparation')
        ->assertSee('action-10')
        ->assertSee('meeting-prep-20');
});

test('attention pilot period excludes older feedback', function () {
    $manager = User::factory()->create(['access_level' => 'management']);
    $staff = User::factory()->create();

    $olderFeedback = AttentionFeedback::query()->create([
        'user_id' => $staff->id,
        'item_key' => 'action-older',
        'source_type' => 'action',
        'source_id' => 1,
        'category' => 'tasks',
        'response' => AttentionFeedback::RESPONSE_USEFUL,
    ]);
    $olderFeedback->forceFill([
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ])->save();

    Livewire::actingAs($manager)
        ->test(AttentionPilotInsights::class)
        ->call('setPeriod', '7')
        ->assertDontSee('action-older')
        ->call('setPeriod', 'all')
        ->assertSee('action-older');
});

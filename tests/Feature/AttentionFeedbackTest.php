<?php

use App\Livewire\NeedsYou;
use App\Models\Action;
use App\Models\AttentionFeedback;
use App\Models\User;
use Livewire\Livewire;

test('staff can rate a surfaced item and change their rating', function () {
    $user = User::factory()->create();
    $action = Action::create([
        'title' => 'Review the funding memo',
        'description' => 'Review the funding memo',
        'assigned_to' => $user->id,
        'status' => Action::STATUS_PENDING,
        'priority' => Action::PRIORITY_HIGH,
        'due_date' => today(),
    ]);

    Livewire::actingAs($user)
        ->test(NeedsYou::class)
        ->call('recordFeedback', 'action-'.$action->id, AttentionFeedback::RESPONSE_USEFUL)
        ->assertDispatched('notify')
        ->call('recordFeedback', 'action-'.$action->id, AttentionFeedback::RESPONSE_NOT_RELEVANT)
        ->assertDispatched('notify');

    $this->assertDatabaseHas('attention_feedback', [
        'user_id' => $user->id,
        'item_key' => 'action-'.$action->id,
        'source_type' => 'action',
        'source_id' => $action->id,
        'rule_key' => 'action_due_soon',
        'category' => 'tasks',
        'response' => AttentionFeedback::RESPONSE_NOT_RELEVANT,
    ]);

    expect(AttentionFeedback::query()->where('item_key', 'action-'.$action->id)->count())->toBe(1);
});

test('staff cannot rate an item that is not in their attention queue', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherAction = Action::create([
        'title' => 'Private assigned task',
        'description' => 'Private assigned task',
        'assigned_to' => $otherUser->id,
        'status' => Action::STATUS_PENDING,
        'priority' => Action::PRIORITY_HIGH,
        'due_date' => today(),
    ]);

    Livewire::actingAs($user)
        ->test(NeedsYou::class)
        ->call('recordFeedback', 'action-'.$otherAction->id, AttentionFeedback::RESPONSE_USEFUL)
        ->assertForbidden();

    $this->assertDatabaseCount('attention_feedback', 0);
});

test('staff can report a missing attention signal', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(NeedsYou::class)
        ->set('missingSignal', 'Surface meetings that have decisions without an owner.')
        ->call('submitMissingSignal')
        ->assertSet('missingSignal', '')
        ->assertDispatched('notify');

    $this->assertDatabaseHas('attention_feedback', [
        'user_id' => $user->id,
        'item_key' => null,
        'response' => AttentionFeedback::RESPONSE_MISSING,
        'note' => 'Surface meetings that have decisions without an owner.',
    ]);
});

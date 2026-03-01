<?php

use App\Models\Action;
use App\Models\SupportSignal;
use App\Models\User;
use App\Services\Support\SupportSignalPolicyService;

test('support signals stay private draft until repeat threshold escalates', function () {
    config()->set('support.signals.repeat_threshold', 3);
    config()->set('support.signals.repeat_window_days', 14);
    config()->set('support.signals.auto_create_management_action', true);

    $manager = User::factory()->create([
        'access_level' => 'management',
    ]);

    $staff = User::factory()->create([
        'access_level' => 'staff',
        'reports_to' => $manager->id,
    ]);

    $policy = app(SupportSignalPolicyService::class);

    $first = $policy->captureFromWorkspace(
        $staff,
        'Need support balancing priorities',
        'I am overwhelmed with overlapping deadlines.',
        false,
        false
    );

    expect($first['signal']->status)->toBe(SupportSignal::STATUS_DRAFT);
    expect($first['signal']->escalation_reason)->toBeNull();
    expect(Action::query()->count())->toBe(0);

    $policy->captureFromWorkspace(
        $staff,
        'Still overloaded',
        'Second signal context',
        false,
        false
    );

    $third = $policy->captureFromWorkspace(
        $staff,
        'Need intervention this week',
        'Third signal raw context should remain private.',
        false,
        false
    );

    $signal = $third['signal']->fresh();
    expect($signal->status)->toBe(SupportSignal::STATUS_ESCALATED);
    expect($signal->escalation_reason)->toBe(SupportSignal::ESCALATION_REPEAT_THRESHOLD);
    expect($signal->window_signal_count)->toBeGreaterThanOrEqual(3);
    expect($signal->followup_action_id)->not->toBeNull();

    $action = Action::query()->findOrFail($signal->followup_action_id);
    expect($action->assigned_to)->toBe($manager->id);
    expect((string) $action->notes)->toContain('consent not granted for raw journaling');
    expect((string) $action->notes)->not->toContain('Third signal raw context should remain private.');
});

test('management digest only includes escalated signals and raw journaling only with consent', function () {
    config()->set('support.signals.repeat_threshold', 5);
    config()->set('support.signals.repeat_window_days', 14);
    config()->set('support.signals.auto_create_management_action', true);

    $manager = User::factory()->create([
        'access_level' => 'management',
    ]);

    $staff = User::factory()->create([
        'access_level' => 'staff',
        'reports_to' => $manager->id,
    ]);

    $policy = app(SupportSignalPolicyService::class);

    $draft = $policy->captureFromWorkspace(
        $staff,
        'Private reflection',
        'private journaling detail',
        false,
        false
    );

    $escalatedNoRaw = $policy->captureFromWorkspace(
        $staff,
        'Please loop in management',
        'no consent context',
        true,
        false
    );

    $escalatedWithRaw = $policy->captureFromWorkspace(
        $staff,
        'Escalate and share details',
        'consented raw context',
        true,
        true
    );

    $digest = collect($policy->managementDigest($manager, 20))->keyBy('id');

    expect($digest->has($draft['signal']->id))->toBeFalse();
    expect($digest->has($escalatedNoRaw['signal']->id))->toBeTrue();
    expect($digest->has($escalatedWithRaw['signal']->id))->toBeTrue();

    $noRawItem = $digest->get($escalatedNoRaw['signal']->id);
    expect($noRawItem['raw_context_shared'])->toBeFalse();
    expect($noRawItem['raw_context'])->toBeNull();

    $withRawItem = $digest->get($escalatedWithRaw['signal']->id);
    expect($withRawItem['raw_context_shared'])->toBeTrue();
    expect((string) $withRawItem['raw_context'])->toContain('consented raw context');
});


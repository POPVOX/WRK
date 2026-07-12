<?php

use App\Livewire\Onboarding;
use App\Models\User;
use Livewire\Livewire;

test('onboarding completion uses livewire navigation', function () {
    $user = User::factory()->create(['profile_completed_at' => null]);

    $component = Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->call('completeOnboarding')
        ->assertRedirect(route('dashboard'));

    expect($component->effects['redirectUsingNavigate'] ?? false)->toBeTrue()
        ->and($user->fresh()->profile_completed_at)->not->toBeNull();
});

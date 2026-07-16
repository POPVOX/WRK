<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.login');
});

test('login screen directs staff to Google Workspace authentication', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('WRK uses Google Workspace sign-in only')
        ->assertSee('Continue with Google');
});

test('navigation menu can be rendered', function () {
    $user = User::factory()->profileCompleted()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response
        ->assertOk()
        ->assertSee('WRKBench')
        ->assertSeeInOrder(['Today', 'Inbox', 'Meetings', 'Projects', 'People', 'Outreach', 'Travel']);
});

test('navigation hides deferred intelligence notifications and outreach destinations', function () {
    $user = User::factory()->admin()->profileCompleted()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('href="'.route('intelligence.index').'"', false)
        ->assertDontSee('href="'.route('notifications.index').'"', false)
        ->assertDontSee('href="'.route('notifications.admin').'"', false)
        ->assertDontSee('href="'.route('communications.outreach').'"', false);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('layout.navigation');

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
});

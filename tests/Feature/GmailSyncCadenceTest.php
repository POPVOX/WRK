<?php

use App\Jobs\SyncGmailMessages;
use App\Livewire\Communications\InboxIndex;
use App\Models\User;
use App\Services\GoogleGmailService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('opening the connected inbox always starts a Gmail sync', function () {
    Queue::fake();
    $user = User::factory()->create([
        'google_access_token' => 'connected-token',
        'google_refresh_token' => 'refresh-token',
        'google_token_expires_at' => now()->addHour(),
    ]);
    $gmail = Mockery::mock(GoogleGmailService::class);
    $gmail->shouldReceive('isConnected')->twice()->with($user)->andReturnTrue();
    app()->instance(GoogleGmailService::class, $gmail);

    Livewire::actingAs($user)
        ->test(InboxIndex::class)
        ->assertSet('gmailConnected', true)
        ->assertSeeHtml('wire:init="syncGmailOnOpen"')
        ->call('syncGmailOnOpen')
        ->assertSet('isSyncingGmail', true)
        ->assertSet('gmailSyncStartedAt', fn ($value) => filled($value))
        ->assertSeeHtml('wire:poll.5s="refreshGmailSyncStatus"')
        ->assertHasNoErrors();

    Queue::assertPushed(SyncGmailMessages::class, fn (SyncGmailMessages $job) => $job->user->is($user));
});

test('the inbox stops polling after a queued Gmail sync completes', function () {
    Queue::fake();
    $user = User::factory()->create([
        'google_access_token' => 'connected-token',
        'google_refresh_token' => 'refresh-token',
        'google_token_expires_at' => now()->addHour(),
        'gmail_import_date' => now()->subHour(),
    ]);
    $gmail = Mockery::mock(GoogleGmailService::class);
    $gmail->shouldReceive('isConnected')->twice()->with($user)->andReturnTrue();
    app()->instance(GoogleGmailService::class, $gmail);

    $component = Livewire::actingAs($user)
        ->test(InboxIndex::class)
        ->call('syncGmailOnOpen')
        ->assertSet('isSyncingGmail', true);

    $user->forceFill(['gmail_import_date' => now()->addSecond()])->save();

    $component
        ->call('refreshGmailSyncStatus')
        ->assertSet('isSyncingGmail', false)
        ->assertSet('gmailSyncStartedAt', null)
        ->assertSet('lastGmailSyncAt', fn ($value) => filled($value))
        ->assertDontSeeHtml('wire:poll.5s="refreshGmailSyncStatus"');
});

test('Gmail sync runs every ten minutes and reconciliation follows two minutes later', function () {
    $events = collect(app(Schedule::class)->events());
    $gmailSync = $events->first(fn ($event) => Str::contains($event->command, 'gmail:sync')
        && ! Str::contains($event->command, '--sync'));
    $reconciliation = $events->first(fn ($event) => Str::contains(
        $event->command,
        'congressional:scan-gmail-changes'
    ));

    expect($gmailSync)->not->toBeNull()
        ->and($gmailSync->expression)->toBe('*/10 * * * *')
        ->and($reconciliation)->not->toBeNull()
        ->and($reconciliation->expression)->toBe('2,12,22,32,42,52 * * * *');
});

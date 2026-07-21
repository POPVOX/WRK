<?php

use App\Livewire\Communications\InboxIndex;
use App\Models\User;
use App\Services\GoogleGmailService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('opening the connected inbox always starts a Gmail sync', function () {
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

});

test('the inbox processes Gmail in browser micro batches until the checkpoint is current', function () {
    $user = User::factory()->create([
        'google_access_token' => 'connected-token',
        'google_refresh_token' => 'refresh-token',
        'google_token_expires_at' => now()->addHour(),
        'gmail_import_date' => now()->subHour(),
    ]);
    $gmail = Mockery::mock(GoogleGmailService::class);
    $gmail->shouldReceive('isConnected')->twice()->with($user)->andReturnTrue();
    $gmail->shouldReceive('syncRecentMessages')
        ->twice()
        ->with($user, 30, 10)
        ->andReturnUsing(function () use ($user) {
            static $batch = 0;
            $batch++;
            $user->forceFill(['gmail_import_date' => now()])->save();

            return [
                'connected' => true,
                'processed' => $batch === 1 ? 10 : 3,
                'imported' => $batch === 1 ? 10 : 3,
                'updated' => 0,
                'errors' => 0,
                'history_id' => (string) $batch,
                'mode' => 'history',
            ];
        });
    app()->instance(GoogleGmailService::class, $gmail);

    $component = Livewire::actingAs($user)
        ->test(InboxIndex::class)
        ->call('syncGmailOnOpen')
        ->assertSet('isSyncingGmail', true);

    $user->forceFill(['gmail_import_date' => now()->addSecond()])->save();

    $component
        ->call('refreshGmailSyncStatus')
        ->assertSet('isSyncingGmail', true)
        ->assertSet('gmailSyncProcessed', 10)
        ->call('refreshGmailSyncStatus')
        ->assertSet('isSyncingGmail', false)
        ->assertSet('gmailSyncStartedAt', null)
        ->assertSet('gmailSyncProcessed', 13)
        ->assertSet('lastGmailSyncAt', fn ($value) => filled($value))
        ->assertDontSeeHtml('wire:poll.5s="refreshGmailSyncStatus"');
});

test('the inbox stops safely when a Gmail micro batch reports an error', function () {
    $user = User::factory()->create([
        'google_access_token' => 'connected-token',
        'google_refresh_token' => 'refresh-token',
        'google_token_expires_at' => now()->addHour(),
    ]);
    $gmail = Mockery::mock(GoogleGmailService::class);
    $gmail->shouldReceive('isConnected')->twice()->with($user)->andReturnTrue();
    $gmail->shouldReceive('syncRecentMessages')
        ->once()
        ->with($user, 30, 10)
        ->andReturn([
            'connected' => true,
            'processed' => 4,
            'imported' => 3,
            'updated' => 0,
            'errors' => 1,
            'history_id' => null,
            'mode' => 'history',
        ]);
    app()->instance(GoogleGmailService::class, $gmail);

    Livewire::actingAs($user)
        ->test(InboxIndex::class)
        ->call('syncGmailOnOpen')
        ->call('refreshGmailSyncStatus')
        ->assertSet('isSyncingGmail', false)
        ->assertSet('gmailSyncProcessed', 4)
        ->assertDispatched('notify', type: 'warning');
});

test('Gmail sync runs inline in bounded batches every ten minutes and reconciliation follows two minutes later', function () {
    $events = collect(app(Schedule::class)->events());
    $gmailSync = $events->first(fn ($event) => Str::contains(
        $event->command,
        'gmail:sync --sync --days=30 --max=50'
    ));
    $reconciliation = $events->first(fn ($event) => Str::contains(
        $event->command,
        'congressional:scan-gmail-changes'
    ));

    expect($gmailSync)->not->toBeNull()
        ->and($gmailSync->expression)->toBe('*/10 * * * *')
        ->and($reconciliation)->not->toBeNull()
        ->and($reconciliation->expression)->toBe('2,12,22,32,42,52 * * * *');
});

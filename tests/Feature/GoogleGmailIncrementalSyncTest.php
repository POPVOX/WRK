<?php

use App\Models\Agent;
use App\Models\User;
use App\Services\Agents\AgentCredentialService;
use App\Services\CongressionalDirectory\CongressionalStaffChangeDetector;
use App\Services\EmailContentFormatter;
use App\Services\GoogleGmailService;

function testableGmailSyncService(bool $expireHistory = false): GoogleGmailService
{
    return new class(app(AgentCredentialService::class), app(CongressionalStaffChangeDetector::class), app(EmailContentFormatter::class), $expireHistory) extends GoogleGmailService
    {
        public array $calls = [];

        public function __construct(
            AgentCredentialService $credentials,
            CongressionalStaffChangeDetector $detector,
            EmailContentFormatter $formatter,
            protected bool $expireHistory
        ) {
            parent::__construct($credentials, $detector, $formatter);
        }

        public function selectMessages(User $user): array
        {
            return $this->messageIdsForSync($user, 30, 250, null);
        }

        protected function incrementalMessageIds(User $user, int $maxMessages, ?Agent $agent): array
        {
            $this->calls[] = 'history';
            if ($this->expireHistory) {
                throw new RuntimeException('startHistoryId is too old', 404);
            }

            return ['message_ids' => ['incremental-1'], 'history_id' => '102', 'mode' => 'history'];
        }

        protected function recentMessageIds(User $user, int $daysBack, int $maxMessages, ?Agent $agent): array
        {
            $this->calls[] = 'recent';

            return ['message_ids' => ['recent-1'], 'history_id' => null, 'mode' => 'recent'];
        }
    };
}

test('gmail sync uses the stored history checkpoint after initial import', function () {
    $user = User::factory()->create(['gmail_history_id' => '100']);
    $service = testableGmailSyncService();

    expect($service->selectMessages($user))->toMatchArray([
        'message_ids' => ['incremental-1'],
        'history_id' => '102',
        'mode' => 'history',
    ])->and($service->calls)->toBe(['history']);
});

test('gmail sync performs one bounded recent scan when a history checkpoint expires', function () {
    $user = User::factory()->create(['gmail_history_id' => '100']);
    $service = testableGmailSyncService(true);

    expect($service->selectMessages($user)['mode'])->toBe('recent')
        ->and($service->calls)->toBe(['history', 'recent']);
});

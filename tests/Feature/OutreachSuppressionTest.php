<?php

use App\Jobs\SendOutreachCampaignRecipient;
use App\Models\OutreachCampaign;
use App\Models\OutreachCampaignRecipient;
use App\Models\OutreachEmailSuppression;
use App\Models\User;
use App\Services\CongressionalDirectory\CongressionalEmailEvidenceService;
use App\Services\GoogleGmailService;
use App\Services\Outreach\OutreachCampaignService;
use App\Services\Outreach\OutreachSuppressionService;

function suppressionCampaign(User $user): OutreachCampaign
{
    return OutreachCampaign::query()->create([
        'user_id' => $user->id,
        'name' => 'Suppression test',
        'campaign_type' => 'bulk',
        'channel' => 'gmail',
        'status' => 'draft',
        'subject' => 'Useful congressional resource',
        'body_text' => 'A useful resource.',
        'send_mode' => 'draft',
    ]);
}

function suppressOutreachEmail(string $email): OutreachEmailSuppression
{
    return OutreachEmailSuppression::query()->create([
        'email_normalized' => strtolower($email),
        'reason' => 'unsubscribe',
        'source_type' => 'test',
        'suppressed_at' => now(),
    ]);
}

test('campaign recipient seeding excludes centrally suppressed addresses', function () {
    $campaign = suppressionCampaign(User::factory()->create());
    suppressOutreachEmail('blocked@example.com');

    $count = app(OutreachCampaignService::class)->seedRecipients($campaign, [
        ['email' => 'blocked@example.com', 'name' => 'Blocked', 'person_id' => null],
        ['email' => 'allowed@example.com', 'name' => 'Allowed', 'person_id' => null],
    ]);

    expect($count)->toBe(1)
        ->and($campaign->recipients()->pluck('email')->all())->toBe(['allowed@example.com']);
});

test('send job rechecks suppression immediately before Gmail delivery', function () {
    $campaign = suppressionCampaign(User::factory()->create());
    $recipient = OutreachCampaignRecipient::query()->create([
        'campaign_id' => $campaign->id,
        'email' => 'late-suppression@example.com',
        'name' => 'Late Suppression',
        'status' => 'queued',
    ]);
    $campaign->update(['status' => 'sending', 'recipients_count' => 1]);
    suppressOutreachEmail($recipient->email);
    $gmail = Mockery::mock(GoogleGmailService::class);
    $gmail->shouldNotReceive('sendMessage');

    (new SendOutreachCampaignRecipient($recipient->id))->handle(
        $gmail,
        app(OutreachCampaignService::class),
        app(OutreachSuppressionService::class),
        app(CongressionalEmailEvidenceService::class)
    );

    expect($recipient->fresh()->status)->toBe('suppressed')
        ->and($recipient->fresh()->sent_at)->toBeNull()
        ->and($campaign->fresh()->status)->toBe('failed');
});

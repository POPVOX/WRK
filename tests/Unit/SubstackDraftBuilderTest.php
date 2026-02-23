<?php

use App\Models\OutreachNewsletter;
use App\Services\Outreach\SubstackDraftBuilder;

it('builds a newsletter draft with signals and links from slack messages', function () {
    $newsletter = new OutreachNewsletter([
        'name' => 'Future-Proofing Congress',
        'default_subject_prefix' => 'Future-Proofing Congress',
    ]);

    $messages = [
        [
            'ts' => '1700000000.000100',
            'author' => 'Alex',
            'text' => 'Great hearing prep notes for this week.',
            'links' => [],
        ],
        [
            'ts' => '1700001000.000200',
            'author' => 'Marci',
            'text' => 'Sharing context doc https://example.org/policy-brief for roundup.',
            'links' => ['https://example.org/policy-brief'],
        ],
    ];

    $builder = new SubstackDraftBuilder;
    $draft = $builder->build($newsletter, $messages, [
        'lead' => 'Marci Harris',
        'publication_url' => 'https://futureproofingcongress.substack.com/',
        'template_sections' => ['Opening note', 'Top signals this cycle', 'Link roundup'],
    ]);

    expect($draft['campaign_name'])->toContain('Future-Proofing Congress');
    expect($draft['subject'])->toContain('Future-Proofing Congress');
    expect($draft['key_signals'])->toHaveCount(2);
    expect($draft['link_roundup'])->toHaveCount(1);
    expect($draft['body_markdown'])->toContain('# Future-Proofing Congress Draft');
    expect($draft['body_markdown'])->toContain('Lead editor: Marci Harris');
    expect($draft['body_markdown'])->toContain('https://example.org/policy-brief');
});

it('handles empty message sets with fallback text', function () {
    $newsletter = new OutreachNewsletter([
        'name' => 'Voice/Mail',
    ]);

    $builder = new SubstackDraftBuilder;
    $draft = $builder->build($newsletter, [], [
        'template_sections' => ['Opening note', 'Top signals this cycle', 'Link roundup'],
    ]);

    expect($draft['key_signals'])->toBeArray()->toHaveCount(0);
    expect($draft['link_roundup'])->toBeArray()->toHaveCount(0);
    expect($draft['body_markdown'])->toContain('No clear Slack signals were detected');
    expect($draft['body_markdown'])->toContain('No links were found');
});

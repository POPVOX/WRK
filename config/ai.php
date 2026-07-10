<?php

return [
    'enabled' => env('AI_FEATURES_ENABLED', true),
    'model' => env('AI_MODEL', 'claude-sonnet-4-6'),
    'meeting_extraction_model' => env('AI_MEETING_EXTRACTION_MODEL', 'claude-haiku-4-5-20251001'),
    'timeout' => env('AI_HTTP_TIMEOUT', 120),
    // Basic per-action soft limits, enforced in Livewire (not middleware)
    'limits' => [
        'chat' => [
            'max' => 30,
            'decay_seconds' => 60,
        ],
        'style_check' => [
            'max' => 10,
            'decay_seconds' => 300,
        ],
    ],
    'safety' => [
        'link' => [
            // Optional allowlist/denylist for link ingestion
            'allow_domains' => explode(',', env('AI_LINK_ALLOW_DOMAINS', '')),
            'deny_domains' => explode(',', env('AI_LINK_DENY_DOMAINS', '')),
            // Max bytes to cache from a fetched link (defaults to 2MB)
            'max_bytes' => (int) env('AI_LINK_MAX_BYTES', 2_000_000),
        ],
    ],
    'metrics' => [
        'enabled' => env('AI_METRICS_ENABLED', true),
    ],
];

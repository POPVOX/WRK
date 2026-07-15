<?php

return [
    'staff_feed_url' => env(
        'CONGRESSIONAL_STAFF_FEED_URL',
        'https://popvox.com/assets/congressional-staff-feed.jsonl.gz'
    ),
    'staff_feed_manifest_url' => env(
        'CONGRESSIONAL_STAFF_FEED_MANIFEST_URL',
        'https://popvox.com/assets/congressional-staff-feed-manifest.json'
    ),
    'import_chunk_size' => (int) env('CONGRESSIONAL_STAFF_IMPORT_CHUNK_SIZE', 500),
    'cbo_minimum_staff' => (int) env('CONGRESSIONAL_CBO_MINIMUM_STAFF', 200),
];

<?php

return [
    'signals' => [
        'repeat_threshold' => (int) env('SUPPORT_SIGNAL_REPEAT_THRESHOLD', 3),
        'repeat_window_days' => (int) env('SUPPORT_SIGNAL_REPEAT_WINDOW_DAYS', 14),
        'auto_create_management_action' => filter_var(
            env('SUPPORT_SIGNAL_AUTO_CREATE_MANAGEMENT_ACTION', true),
            FILTER_VALIDATE_BOOL
        ),
    ],
];


<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Approval Gate
    |--------------------------------------------------------------------------
    |
    | Centralized risk-based gate for AI/automation initiated writes. Low risk
    | actions are allowed by default. Medium/high risk actions require
    | management approval unless explicitly overridden.
    |
    */
    'enabled' => filter_var(env('APPROVAL_GATE_ENABLED', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Default Risk Map
    |--------------------------------------------------------------------------
    |
    | Risk levels: low, medium, high.
    |
    */
    'risk_map' => [
        'trip.auto_apply' => 'medium',
        'agent.autonomous_execute' => 'medium',
        'email.send_ai' => 'high',
        'calendar.write_ai' => 'high',
    ],
];

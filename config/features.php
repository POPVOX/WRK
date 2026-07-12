<?php

return [
    // Preserve the agent permission model while keeping the current admin UI focused.
    'agent_governance_ui' => (bool) env('AGENT_GOVERNANCE_UI', false),
];

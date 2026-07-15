<?php

return [
    // Preserve the agent permission model while keeping the current admin UI focused.
    'agent_governance_ui' => (bool) env('AGENT_GOVERNANCE_UI', false),
    // Keep the new directory private until the importer and review experience are ready.
    'congressional_directory_ui' => (bool) env('CONGRESSIONAL_DIRECTORY_UI', false),
];

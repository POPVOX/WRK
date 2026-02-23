<?php

return [
    'substack' => [
        'default_days_back' => (int) env('OUTREACH_SUBSTACK_DEFAULT_DAYS_BACK', 7),
        'default_message_limit' => (int) env('OUTREACH_SUBSTACK_DEFAULT_MESSAGE_LIMIT', 80),
        'presets' => [
            [
                'slug' => 'modparl',
                'name' => 'Modern Parliament',
                'publication_url' => 'https://modparl.substack.com/',
                'default_subject_prefix' => 'Modern Parliament',
                'cadence' => 'weekly',
                'lead' => env('OUTREACH_LEAD_MODPARL', ''),
                'slack_channel_id' => env('OUTREACH_SLACK_CHANNEL_MODPARL', ''),
                'project_match_terms' => ['modern parliament', 'modparl'],
                'template_sections' => [
                    'Opening note',
                    'Parliament modernization signals',
                    'Global examples and references',
                    'What to watch next',
                ],
            ],
            [
                'slug' => 'futureproofingcongress',
                'name' => 'Future-Proofing Congress',
                'publication_url' => 'https://futureproofingcongress.substack.com/',
                'default_subject_prefix' => 'Future-Proofing Congress',
                'cadence' => 'weekly',
                'lead' => env('OUTREACH_LEAD_FUTUREPROOFING', ''),
                'slack_channel_id' => env('OUTREACH_SLACK_CHANNEL_FUTUREPROOFING', ''),
                'project_match_terms' => ['future-proofing congress', 'futureproofing'],
                'template_sections' => [
                    'Opening note',
                    'Congressional operations signals',
                    'Tools and pilots',
                    'Actions for staff and partners',
                ],
            ],
            [
                'slug' => 'voicemailgov',
                'name' => 'Voice/Mail',
                'publication_url' => 'https://voicemailgov.substack.com/',
                'default_subject_prefix' => 'Voice/Mail',
                'cadence' => 'weekly',
                'lead' => env('OUTREACH_LEAD_VOICEMAIL', ''),
                'slack_channel_id' => env('OUTREACH_SLACK_CHANNEL_VOICEMAIL', ''),
                'project_match_terms' => ['voice/mail', 'voicemail', 'newsletter'],
                'template_sections' => [
                    'Opening note',
                    'What moved this week',
                    'Notable reads',
                    'Why it matters now',
                ],
            ],
            [
                'slug' => 'caseworknavigator',
                'name' => 'Casework Navigator',
                'publication_url' => 'https://caseworknavigator.substack.com/',
                'default_subject_prefix' => 'Casework Navigator',
                'cadence' => 'biweekly',
                'lead' => env('OUTREACH_LEAD_CASEWORK', ''),
                'slack_channel_id' => env('OUTREACH_SLACK_CHANNEL_CASEWORK', ''),
                'project_match_terms' => ['casework navigator', 'casework'],
                'template_sections' => [
                    'Opening note',
                    'Casework developments',
                    'Implementation resources',
                    'Next-step prompts',
                ],
            ],
        ],
    ],
];

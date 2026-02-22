<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', 'http://localhost:8000/google/callback'),
        'login_redirect_uri' => env('GOOGLE_LOGIN_REDIRECT_URI', env('APP_URL', 'http://localhost').'/auth/google/callback'),
        'workspace_scopes' => array_values(array_filter(array_map(
            static fn ($scope) => trim((string) $scope),
            explode(',', (string) env(
                'GOOGLE_WORKSPACE_SCOPES',
                'https://www.googleapis.com/auth/calendar.readonly,https://www.googleapis.com/auth/gmail.readonly,https://www.googleapis.com/auth/gmail.send,https://www.googleapis.com/auth/gmail.compose'
            ))
        ))),
    ],

    'box' => [
        'base_uri' => env('BOX_API_BASE_URI', 'https://api.box.com/2.0'),
        'access_token' => env('BOX_ACCESS_TOKEN'),
        'token_url' => env('BOX_AUTH_TOKEN_URL', 'https://api.box.com/oauth2/token'),
        'client_id' => env('BOX_CLIENT_ID'),
        'client_secret' => env('BOX_CLIENT_SECRET'),
        'subject_type' => env('BOX_SUBJECT_TYPE', 'enterprise'),
        'enterprise_id' => env('BOX_ENTERPRISE_ID'),
        'user_id' => env('BOX_USER_ID'),
        'root_folder_id' => env('BOX_ROOT_FOLDER_ID', '0'),
        'projects_folder_id' => env('BOX_PROJECTS_FOLDER_ID', env('BOX_ROOT_FOLDER_ID', '0')),
        'auto_provision_project_folders' => filter_var(env('BOX_AUTO_PROVISION_PROJECT_FOLDERS', true), FILTER_VALIDATE_BOOL),
        'sync_page_size' => (int) env('BOX_SYNC_PAGE_SIZE', 100),
        'sync_max_depth' => (int) env('BOX_SYNC_MAX_DEPTH', 6),
        'webhook' => [
            'enforce_signature' => filter_var(env('BOX_WEBHOOK_ENFORCE_SIGNATURE', true), FILTER_VALIDATE_BOOL),
            'primary_signature_key' => env('BOX_WEBHOOK_PRIMARY_KEY'),
            'secondary_signature_key' => env('BOX_WEBHOOK_SECONDARY_KEY'),
        ],
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'repo' => env('GITHUB_REPO', 'POPVOX/WRK'),
        'auto_create_issues' => env('GITHUB_AUTO_CREATE_ISSUES', false),
    ],

];

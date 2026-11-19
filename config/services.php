<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-5-5'),
        'effort' => env('ANTHROPIC_EFFORT', 'high'),

        // Cheap extraction and ranking tasks run on smaller models; drafting and fact-checking
        // documents keep the default model above. Override per task with ANTHROPIC_<TASK>_MODEL / _EFFORT.
        'tasks' => [
            'match' => [
                'model' => env('ANTHROPIC_MATCH_MODEL', 'claude-haiku-5-5'),
                'effort' => env('ANTHROPIC_MATCH_EFFORT', 'medium'),
            ],
            'posting' => [
                'model' => env('ANTHROPIC_POSTING_MODEL', 'claude-haiku-5-5'),
                'effort' => env('ANTHROPIC_POSTING_EFFORT', 'low'),
            ],
            'boards' => [
                'model' => env('ANTHROPIC_BOARDS_MODEL', 'claude-haiku-5-5'),
                'effort' => env('ANTHROPIC_BOARDS_EFFORT', 'low'),
            ],
            'ledger' => [
                'model' => env('ANTHROPIC_LEDGER_MODEL', 'claude-sonnet-5-5'),
                'effort' => env('ANTHROPIC_LEDGER_EFFORT', 'medium'),
            ],
        ],
    ],

    'google_analytics' => [
        'measurement_id' => env('VITE_GA_MEASUREMENT_ID'),
    ],

    'jooble' => [
        'key' => env('JOOBLE_API_KEY'),
    ],

    'careerjet' => [
        'key' => env('CAREERJET_API_KEY'),
        'locale' => env('CAREERJET_LOCALE', 'nl_NL'),
    ],

    'polar' => [
        'token' => env('POLAR_ACCESS_TOKEN'),
        'webhook_secret' => env('POLAR_WEBHOOK_SECRET'),
        'product_id' => env('POLAR_PRODUCT_ID'),
        // "production" or "sandbox".
        'server' => env('POLAR_SERVER', 'production'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

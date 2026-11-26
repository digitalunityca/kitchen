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

    // Voice chat on the dashboard (App\Modules\AiChat). The key stays on the
    // server; the tablet only ever gets a one-minute client secret.
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'realtime_model' => env('OPENAI_REALTIME_MODEL', 'gpt-realtime-2.1'),
        'realtime_voice' => env('OPENAI_REALTIME_VOICE', 'marin'),
        // Answers the voice chat's search_web tool (Responses API + web_search).
        'search_model' => env('OPENAI_SEARCH_MODEL', 'gpt-5-mini'),
        'homework_model' => env('OPENAI_HOMEWORK_MODEL', 'gpt-5-mini'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

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

    'tata_aig' => [
        'base_url' => env('TATA_AIG_BASE_URL'),
        'api_key' => env('TATA_AIG_API_KEY'),
        'client_id' => env('TATA_AIG_CLIENT_ID'),
        'client_secret' => env('TATA_AIG_CLIENT_SECRET'),
        'product_code' => env('TATA_AIG_PRODUCT_CODE', '3187'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
    ],
    'gemini' => [
        'api_key'              => env('GEMINI_API_KEY', ''),
        'model'                => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'confidence_threshold' => (int) env('GEMINI_CONFIDENCE_THRESHOLD', 80),
        'batch_size'           => (int) env('GEMINI_BATCH_SIZE', 50),
    ],

];

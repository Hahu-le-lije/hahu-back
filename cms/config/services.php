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

    'clerk' => [
        'secret_key' => env('CLERK_SECRET_KEY'),
        'frontend_url' => env('FRONTEND_URL'),
    ],
    'parent_service' => [
        'url' => env('PARENT_SERVICE_URL', env('APP_URL', 'http://localhost')),
    ],
    'child_service' => [
        'url' => env('CHILD_SERVICE_URL', 'http://localhost:8001'),
        'secret_token' => env('CHILD_SERVICE_TOKEN', env('INTERNAL_SERVICE_TOKEN')),
    ],
    'sync_service' => [
        'url' => env('SYNC_SERVICE_URL', 'http://localhost:8002'),
        'secret_token' => env('SYNC_SERVICE_TOKEN', env('INTERNAL_SERVICE_TOKEN')),
    ],
    'service_auth' => [
        'cms' => env('CMS_SERVICE_SECRET', 'cms_secret_key'),
    ],

];

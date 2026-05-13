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
        'secret_key' => env('CLERK_SECRET_KEY')
    ],

    'sync' => [
        'url' => env('SYNC_SERVICE_URL', 'http://localhost:8000'),
        'secret_token' => env('SYNC_SERVICE_SECRET_TOKEN'),
    ],

    'child_service' => [
        'url' => env('CHILD_SERVICE_URL', 'http://child-service'),
        'secret_token' => env('CHILD_SERVICE_SECRET_TOKEN'),
    ],

    'user_service' => [
        'url' => env('USER_SERVICE_URL', 'http://user-service'),
    ],

    'service_auth' => [
        'recommendation-service' => env('RECOMMENDATION_SERVICE_SECRET'),
    ],

];

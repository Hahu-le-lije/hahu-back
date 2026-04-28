<?php

return [
    'basic' => [
        'fee' => env('SUBSCRIPTION_TYPE_BASIC_FEE'),
        'duration' => env('SUBSCRIPTION_TYPE_BASIC_DURATION', '400 month'),
    ],
    'premium' => [
        'fee' => env('SUBSCRIPTION_TYPE_PREMIUM_FEE'),
        'duration' => env('PREMIUM_SUBSCRIPTION_DURATION'),
    ],
    'ultimate' => [
        'fee' => env('SUBSCRIPTION_TYPE_ULTIMATE_FEE'),
        'duration' => env('ULTIMATE_SUBSCRIPTION_DURATION'),
    ],
    'return_url' => env('RETURN_URL'),
];
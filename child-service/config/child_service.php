<?php

return [
    'child_jwt_secret' => env('CHILD_SERVICE_JWT_SECRET', env('JWT_SECRET', env('APP_KEY'))),
    'parent_jwt_secret' => env('PARENT_SERVICE_JWT_SECRET', env('JWT_SECRET', env('APP_KEY'))),
    'child_token_ttl_minutes' => (int) env('CHILD_TOKEN_TTL_MINUTES', 120),
    'parent_token_audience' => env('PARENT_TOKEN_AUDIENCE'),
    'child_token_audience' => env('CHILD_TOKEN_AUDIENCE', 'child-service'),
    'pin_length' => (int) env('CHILD_PIN_LENGTH', 6),
];

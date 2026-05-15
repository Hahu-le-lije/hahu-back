<?php

return [
    'child_jwt_secret' => env('CHILD_SERVICE_JWT_SECRET', env('JWT_SECRET', env('APP_KEY'))),
    'child_token_ttl_minutes' => (int) env('CHILD_TOKEN_TTL_MINUTES', 120),
    'child_token_audience' => env('CHILD_TOKEN_AUDIENCE', 'child-service'),
    'clerk_jwt_key' => env('CLERK_JWT_KEY'),
    'clerk_issuer' => env('CLERK_ISSUER'),
    'clerk_authorized_parties' => array_filter(array_map(
        'trim',
        explode(',', (string) env('CLERK_AUTHORIZED_PARTIES', ''))
    )),
    'pin_length' => (int) env('CHILD_PIN_LENGTH', 6),
];

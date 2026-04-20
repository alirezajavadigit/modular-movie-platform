<?php

return [
    'name' => 'Auth',

    'otp_length' => 6,
    'otp_ttl' => 5,

    'notification_channel' => env('AUTH_NOTIFICATION_CHANNEL', 'email'),

    'rate_limits' => [
        'register' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
        'login' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],
        'refresh' => [
            'max_attempts' => 20,
            'decay_minutes' => 1,
        ],
        'forgot_password' => [
            'max_attempts' => 5,
            'decay_minutes' => 10,
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],
];

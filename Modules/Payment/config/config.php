<?php

return [
    'name' => 'Payment',

    'gateways' => [
        'zibal' => [
            'merchant'     => env('ZIBAL_MERCHANT', ''),
            'callback_url' => env('ZIBAL_CALLBACK_URL', ''),
        ],

        'zarinpal' => [
            'merchant_id'  => env('ZARINPAL_MERCHANT_ID', ''),
            'callback_url' => env('ZARINPAL_CALLBACK_URL', ''),
        ],

        'paypal' => [
            'client_id'     => env('PAYPAL_CLIENT_ID', ''),
            'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
            'mode'          => env('PAYPAL_MODE', 'sandbox'),
            'callback_url'  => env('PAYPAL_CALLBACK_URL', ''),
            'cancel_url'    => env('PAYPAL_CANCEL_URL', ''),
        ],

        'stripe' => [
            'secret_key'      => env('STRIPE_SECRET_KEY', ''),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
            'callback_url'    => env('STRIPE_CALLBACK_URL', ''),
            'cancel_url'      => env('STRIPE_CANCEL_URL', ''),
        ],
    ],
];

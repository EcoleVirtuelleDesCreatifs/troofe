<?php

return [
    'payments' => [
        'driver' => env('PAYMENT_DRIVER', 'simulation'),
    ],
    'cinetpay' => [
        'api_key' => env('CINETPAY_API_KEY'),
        'site_id' => env('CINETPAY_SITE_ID'),
        'secret_key' => env('CINETPAY_SECRET_KEY'),
        'endpoint' => 'https://api-checkout.cinetpay.com/v2',
    ],
];

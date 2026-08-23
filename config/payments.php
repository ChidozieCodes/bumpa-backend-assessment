<?php

return [
    'driver' => env('PAYMENT_DRIVER', 'log'),
    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],
];

<?php

return [
    'secret_key' => env('PAYMONGO_SECRET_KEY'),
    'public_key' => env('PAYMONGO_PUBLIC_KEY'),
    'webhook_signing_secret' => env('PAYMONGO_WEBHOOK_SIGNING_SECRET', env('PAYMONGO_WEBHOOK_SECRET')),
    'mode' => env('PAYMONGO_MODE', 'test'),
    'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),
    'success_url' => env('PAYMONGO_SUCCESS_URL'),
    'cancel_url' => env('PAYMONGO_CANCEL_URL'),
    'trial_days' => (int) env('PAYMONGO_TRIAL_DAYS', 14),
];

<?php

return [
    'provider' => env('PAYMENT_PROVIDER', 'simulator'),
    'api_base_url' => env('PAYMENT_API_BASE_URL', env('APP_URL').'/api/v1/simulator'),
    'webhook_url' => env('PAYMENT_WEBHOOK_URL', env('APP_URL').'/api/v1/webhooks/payments/simulator'),
    'methods' => [
        'credit_card' => '信用卡',
        'bank_transfer' => 'ATM 轉帳',
        'mobile_payment' => '行動支付',
    ],
];

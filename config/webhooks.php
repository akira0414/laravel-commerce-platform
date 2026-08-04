<?php
return ['tolerance_seconds' => (int) env('WEBHOOK_TOLERANCE_SECONDS', 300), 'payments' => ['secret' => env('PAYMENT_WEBHOOK_SECRET')], 'shipping' => ['secret' => env('SHIPPING_WEBHOOK_SECRET')]];

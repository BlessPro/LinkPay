<?php

return [
    'trial_days' => 7,

    'payments' => [
        'price_range' => 'GHS 30-50 / month',
        // Platform commission charged on each successful payment (stored internally).
        'commission_percent' => '0.01', // 1%
        'commission_text' => '1% per successful payment',
    ],

    'promotion' => [
        'price_range' => 'GHS 60-100 / month',
        'commission_text' => 'No payment commission (payments disabled)',
    ],

    'enterprise' => [
        'price_range' => 'Contact sales',
    ],
];


<?php

return [
    'trial_days' => 9,

    'starter' => [
        'name' => 'Starter',
        'price_monthly' => 15,
        'price_text' => 'GHS 15 / month',
        'best_for' => 'Individuals starting out',
        'transaction_fee' => '1.9%',
        'limits' => [
            'products' => 100,
            'orders' => 'Unlimited',
            'admins' => 1,
            'team_members' => 'No',
        ],
        'features' => [
            'Public storefront + product pages',
            'Unlimited orders',
            'Basic dashboard',
            '1 admin user',
            'Cart + checkout + payment links',
        ],
    ],

    'growth' => [
        'name' => 'Growth',
        'price_monthly' => 30,
        'price_text' => 'GHS 30 / month',
        'best_for' => 'Growing sellers',
        'transaction_fee' => '1.25%',
        'limits' => [
            'products' => 300,
            'orders' => 'Unlimited',
            'admins' => 1,
            'team_members' => 'No',
        ],
        'features' => [
            'Everything in Starter',
            'More products (up to 400)',
            'Up to 3 admin users',
            'Priority support',
            'Stronger operational limits',
        ],
    ],

    'enterprise' => [
        'name' => 'Enterprise',
        'price_monthly' => 70,
        'price_text' => 'GHS 70 / month',
        'best_for' => 'Businesses and teams',
        'transaction_fee' => '1%',
        'limits' => [
            'products' => 'Unlimited',
            'orders' => 'Unlimited',
            'admins' => 10,
            'team_members' => 'Up to 10',
        ],
        'features' => [
            'Everything in Growth',
            'Unlimited products',
            'Up to 10 admin users',
            'Best performance limits',
            'Top-priority support',
        ],
    ],

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

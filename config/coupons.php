<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Coupon Abuse Guard
    |--------------------------------------------------------------------------
    |
    | Block re-use of the same coupon code from the same IP for the configured
    | number of hours. Set to 0 to disable IP-based blocking.
    |
    */
    'ip_reuse_block_hours' => (int) env('COUPON_IP_REUSE_BLOCK_HOURS', 24),
];

<?php

namespace App\Support;

class Email
{
    public static function placeholder(string $reference): string
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'linkpay.app';

        if (! $domain || $domain === 'localhost' || $domain === '127.0.0.1') {
            $domain = 'linkpay.app';
        }

        return 'buyer+'.$reference.'@'.$domain;
    }
}

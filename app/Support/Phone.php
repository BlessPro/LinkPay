<?php

namespace App\Support;

class Phone
{
    public static function normalize(?string $raw, string $country = '+233'): ?string
    {
        if (! $raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if (! $digits) {
            return null;
        }

        $countryDigits = ltrim($country, '+');
        if (str_starts_with($digits, $countryDigits)) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $country.$digits;
    }

    public static function isValidGh(?string $raw): bool
    {
        $normalized = self::normalize($raw, '+233');
        if (! $normalized) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $normalized);

        return strlen($digits) === 12 && str_starts_with($digits, '233');
    }
}

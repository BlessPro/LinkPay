<?php

namespace App\Support;

class Money
{
    public static function toMinor(string $amount): int
    {
        if (function_exists('bcmul')) {
            return (int) bcmul($amount, '100', 0);
        }

        return (int) round(((float) $amount) * 100);
    }

    public static function compare(string $left, string $right): int
    {
        if (function_exists('bccomp')) {
            return bccomp($left, $right, 2);
        }

        $leftValue = round(((float) $left), 2);
        $rightValue = round(((float) $right), 2);

        return $leftValue <=> $rightValue;
    }

    public static function subtract(string $left, string $right): string
    {
        if (function_exists('bcsub')) {
            return bcsub($left, $right, 2);
        }

        return number_format(((float) $left) - ((float) $right), 2, '.', '');
    }

    public static function add(string $left, string $right): string
    {
        if (function_exists('bcadd')) {
            return bcadd($left, $right, 2);
        }

        return number_format(((float) $left) + ((float) $right), 2, '.', '');
    }

    public static function multiply(string $amount, int $multiplier): string
    {
        if (function_exists('bcmul')) {
            return bcmul($amount, (string) $multiplier, 2);
        }

        return number_format(((float) $amount) * $multiplier, 2, '.', '');
    }

    public static function format(string $amount, string $currency): string
    {
        return $currency.' '.number_format((float) $amount, 2, '.', ',');
    }

    public static function fromMinor(int|string $amount): string
    {
        if (! is_numeric($amount)) {
            return '0.00';
        }

        if (function_exists('bcmul') && function_exists('bcdiv')) {
            return bcdiv((string) $amount, '100', 2);
        }

        return number_format(((float) $amount) / 100, 2, '.', '');
    }

    public static function percent(string $amount, string $percent): string
    {
        if (function_exists('bcmul')) {
            return bcmul($amount, $percent, 2);
        }

        return number_format(((float) $amount) * (float) $percent, 2, '.', '');
    }
}

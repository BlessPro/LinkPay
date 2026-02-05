<?php

namespace App\Support;

class WhatsApp
{
    /**
     * Build a WhatsApp chat URL (wa.me) from an E.164-like phone number.
     * WhatsApp expects digits only (no "+") in the path.
     */
    public static function chatUrl(string $phone, string $text = ''): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        $base = 'https://wa.me/'.$digits;

        if ($text !== '') {
            $base .= '?text='.rawurlencode($text);
        }

        return $base;
    }
}


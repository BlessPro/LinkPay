<?php

namespace App\Support;

class MomoNetwork
{
    public const MTN = 'MTN';
    public const TELECEL = 'TELECEL';
    public const AIRTELTIGO = 'AIRTELTIGO';

    /**
     * Detect Ghana MoMo network from a phone/wallet number.
     * Returns one of the constants above or null if unknown.
     */
    public static function detectGh(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if (! $digits) {
            return null;
        }

        // Normalize to local national format for prefix detection.
        // Accepted examples:
        // - 0541900229 (10 digits)
        // - 541900229 (9 digits)
        // - +233541900229 / 233541900229
        if (str_starts_with($digits, '233') && strlen($digits) >= 12) {
            $digits = substr($digits, 3); // now local 9 digits
        }

        $prefix = null;
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $prefix = substr($digits, 0, 3);
        } elseif (strlen($digits) === 9) {
            // Reconstruct the common "0xx" prefix.
            $prefix = '0'.substr($digits, 0, 2);
        }

        if (! $prefix) {
            return null;
        }

        // Prefix mapping (Ghana).
        $mtn = ['024', '025', '053', '054', '055', '059'];
        $telecel = ['020', '050'];
        $airteltigo = ['026', '027', '056', '057'];

        if (in_array($prefix, $mtn, true)) {
            return self::MTN;
        }
        if (in_array($prefix, $telecel, true)) {
            return self::TELECEL;
        }
        if (in_array($prefix, $airteltigo, true)) {
            return self::AIRTELTIGO;
        }

        return null;
    }
}


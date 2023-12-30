<?php

declare(strict_types=1);

use Bakame\Aide\Base32\Base32;

if (!defined('PHP_BASE32_ASCII')) {
    define('PHP_BASE32_ASCII', 1);
}

if (!defined('PHP_BASE32_HEX')) {
    define('PHP_BASE32_HEX', 2);
}

if (!function_exists('base32_encode')) {
    function base32_encode(string $decoded, int $encoding = PHP_BASE32_ASCII): string
    {
        return match ($encoding) {
            PHP_BASE32_HEX => Base32::Hex->encode($decoded),
            default => Base32::Ascii->encode($decoded),
        };
    }
}

if (!function_exists('base32_decode')) {
    function base32_decode(string $encoded, int $encoding = PHP_BASE32_ASCII, bool $strict = false): string|false
    {
        $base32 = match ($encoding) {
            PHP_BASE32_HEX => Base32::Hex,
            default => Base32::Ascii,
        };

        if (!$strict) {
            return $base32->decode($encoded);
        }

        try {
            return $base32->decodeOrFail($encoded);
        } catch (Throwable) {
            return false;
        }
    }
}

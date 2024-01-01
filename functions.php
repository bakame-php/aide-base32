<?php

declare(strict_types=1);

use Bakame\Aide\Base32\Base32;
use Bakame\Aide\Base32\Base32Exception;

defined('PHP_BASE32_ASCII') || define('PHP_BASE32_ASCII', 1);
defined('PHP_BASE32_HEX') || define('PHP_BASE32_HEX', 2);

if (!function_exists('base32_encode')) {
    function base32_encode(string $decoded, int $encoding = PHP_BASE32_ASCII): string
    {
        $base32 = match ($encoding) {
            PHP_BASE32_HEX => Base32::Hex,
            default => Base32::Ascii,
        };

        return $base32->encode($decoded);
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
            return $base32->decodeLax($encoded);
        }

        try {
            return $base32->decode($encoded);
        } catch (Base32Exception) {
            return false;
        }
    }
}

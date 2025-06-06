<?php

declare(strict_types=1);

use Bakame\Aide\Base32\Base32;

defined('PHP_BASE32_ASCII') || define('PHP_BASE32_ASCII', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567');
defined('PHP_BASE32_HEX') || define('PHP_BASE32_HEX', '0123456789ABCDEFGHIJKLMNOPQRSTUV');

if (!function_exists('base32_encode')) {
    /**
     * @param non-empty-string $alphabet
     * @param non-empty-string $padding
     */
    function base32_encode(
        string $decoded,
        string $alphabet = PHP_BASE32_ASCII,
        string $padding = '=',
    ): string {
        return (new Base32($alphabet, $padding))->encode($decoded);
    }
}

if (!function_exists('base32_decode')) {
    /**
     * @param non-empty-string $alphabet
     * @param non-empty-string $padding
     */
    function base32_decode(
        string $encoded,
        string $alphabet = PHP_BASE32_ASCII,
        string $padding = '=',
        bool $strict = false
    ): ?string {
        try {
            return (new Base32($alphabet, $padding))->decode($encoded, $strict);
        } catch (RuntimeException) {
            return null;
        }
    }
}

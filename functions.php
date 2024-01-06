<?php

declare(strict_types=1);

use Bakame\Aide\Base32\Base32;
use Bakame\Aide\Base32\Base32Exception;

defined('PHP_BASE32_ASCII') || define('PHP_BASE32_ASCII', Base32::ASCII);
defined('PHP_BASE32_HEX') || define('PHP_BASE32_HEX', Base32::HEX);

if (!function_exists('base32_encode')) {
    function base32_encode(
        string $decoded,
        string $alphabet = PHP_BASE32_ASCII,
        bool $usePadding = true
    ): string {
        return Base32::encode($decoded, $alphabet, $usePadding ? Base32::PADDING_CHARACTER : '');
    }
}

if (!function_exists('base32_decode')) {
    function base32_decode(
        string $encoded,
        string $alphabet = PHP_BASE32_ASCII,
        bool $usePadding = true,
        bool $strict = false
    ): string|false {

        if (!$strict) {
            return Base32::decodeLax($encoded, $alphabet, $usePadding ? Base32::PADDING_CHARACTER : '');
        }

        try {
            return Base32::decode($encoded, $alphabet, $usePadding ? Base32::PADDING_CHARACTER : '');
        } catch (Base32Exception) {
            return false;
        }
    }
}

<?php

declare(strict_types=1);

defined('PHP_BASE32_ASCII') || define('PHP_BASE32_ASCII', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567');
defined('PHP_BASE32_HEX') || define('PHP_BASE32_HEX', '0123456789ABCDEFGHIJKLMNOPQRSTUV');

if (!function_exists('base32_encode')) {
    function base32_encode(
        string $decoded,
        string $alphabet = PHP_BASE32_ASCII,
    ): string {
        if ('' === $decoded) {
            return '';
        }

        $encoded = '';
        $n = 0;
        $bitLen = 0;
        $val = 0;
        $len = strlen($decoded);
        $decoded .= str_repeat(chr(0), 4);
        $chars = (array) unpack('C*', $decoded);
        $padding = '=';
        $alphabet .= $padding;

        while ($n < $len || 0 !== $bitLen) {
            if ($bitLen < 5) {
                $val = $val << 8;
                $bitLen += 8;
                $n++;
                $val += $chars[$n];
            }
            $shift = $bitLen - 5;
            $encoded .= ($n - (int)($bitLen > 8) > $len && 0 == $val) ? $padding : $alphabet[$val >> $shift];
            $val = $val & ((1 << $shift) - 1);
            $bitLen -= 5;
        }

        return $encoded;
    }
}

if (!function_exists('base32_decode')) {
    function base32_decode(
        string $encoded,
        string $alphabet = PHP_BASE32_ASCII,
        bool $strict = false
    ): string|false {
        if ('' === $encoded) {
            return '';
        }

        if (!$strict) {
            $encoded = strtoupper($encoded);
        }

        if (strtoupper($encoded) !== $encoded) {
            return false;
        }

        $padding = '=';
        $remainder = strlen($encoded) % 8;
        if (0 !== $remainder) {
            if ($strict) {
                return false;
            }

            $encoded .= str_repeat($padding, $remainder);
        }

        if (strspn($encoded, $alphabet.$padding) !== strlen($encoded)) {
            if ($strict) {
                return false;
            }
            $encoded = preg_replace('/[^'.preg_quote($alphabet.$padding, '/').']/', '', $encoded);
            if ('' === $encoded || null === $encoded) {
                return '';
            }
        }

        $inside = rtrim($encoded, $padding);
        if (str_contains($inside, $padding)) {
            if ($strict) {
                return false;
            }
            $encoded = str_replace($padding, '', $inside).substr($encoded, strlen($inside));
        }

        if ($strict && 1 !== preg_match('/^[^'.$padding.']+(('.$padding.'){3,4}|('.$padding.'){6}|'.$padding.')?$/', $encoded)) {
            return false;
        }

        $decoded = '';
        $len = strlen($encoded);
        $n = 0;
        $bitLen = 5;
        $mapping = array_combine(str_split($alphabet.$padding), [...range(0, 31), 0]);
        $val = $mapping[$encoded[0]];

        while ($n < $len) {
            if ($bitLen < 8) {
                $val = $val << 5;
                $bitLen += 5;
                $n++;
                $pentet = $encoded[$n] ?? $padding;
                if ($padding === $pentet) {
                    $n = $len;
                }
                $val += $mapping[$pentet];
                continue;
            }

            $shift = $bitLen - 8;
            $decoded .= chr($val >> $shift);
            $val = $val & ((1 << $shift) - 1);
            $bitLen -= 8;
        }

        return $decoded;
    }
}

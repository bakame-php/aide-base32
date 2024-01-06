<?php

declare(strict_types=1);

namespace Bakame\Aide\Base32;

use function chr;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_repeat;
use function strlen;
use function strspn;
use function strtoupper;
use function unpack;

/**
 * An Enumeration to allow Base32 encoding/decoding according to RFC4648.
 *
 * Based on https://github.com/ChristianRiesen/base32/blob/master/src/Base32.php class
 */
final class Base32
{
    public const PADDING_CHARACTER = '=';
    public const ASCII = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    public const HEX = '0123456789ABCDEFGHIJKLMNOPQRSTUV';

    public static function encode(
        string $decoded,
        string $alphabet = self::ASCII,
        string $padding = self::PADDING_CHARACTER
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

    /**
     * @throws Base32Exception if the encoded string is invalid
     */
    public static function decode(
        string $encoded,
        string $alphabet = self::ASCII,
        string $padding = self::PADDING_CHARACTER,
        bool $strict = false
    ): string {
        if ('' === $encoded) {
            return '';
        }

        if (!$strict) {
            $encoded = strtoupper($encoded);
        }

        if (strtoupper($encoded) !== $encoded) {
            throw new Base32Exception('The encoded string contains lower-cased characters which is forbidden on strict mode.');
        }

        $remainder = strlen($encoded) % 8;
        if (0 !== $remainder) {
            if ($strict) {
                throw new Base32Exception('The encoded string length is not a multiple of 8.');
            }

            $encoded .= str_repeat($padding, $remainder);
        }

        if (strspn($encoded, $alphabet.$padding) !== strlen($encoded)) {
            if ($strict) {
                throw new Base32Exception('The encoded string contains characters outside of the base32 alphabet.');
            }
            $encoded = preg_replace('/[^'.preg_quote($alphabet.$padding, '/').']/', '', $encoded);
            if ('' === $encoded || null === $encoded) {
                return '';
            }
        }

        $inside = rtrim($encoded, $padding);
        if (str_contains($inside, $padding)) {
            if ($strict) {
                throw new Base32Exception('A padding character is contained in the middle of the encoded string.');
            }
            $encoded = str_replace($padding, '', $inside).substr($encoded, strlen($inside));
        }

        if ($strict && '' !== $padding && 1 !== preg_match('/^[^'.$padding.']+(('.$padding.'){3,4}|('.$padding.'){6}|'.$padding.')?$/', $encoded)) {
            throw new Base32Exception('The encoded string contains an invalid padding length.');
        }

        $decoded = '';
        $mapping = array_combine(str_split($alphabet.$padding), [...range(0, 31), 0]);
        $len = strlen($encoded);
        $n = 0;
        $bitLen = 5;
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

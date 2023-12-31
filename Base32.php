<?php

declare(strict_types=1);

namespace Bakame\Aide\Base32;

use function chr;
use function preg_match;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_repeat;
use function strlen;
use function strtoupper;
use function unpack;

/**
 * An Enumeration to allow Base32 encoding/decoding according to RFC4648.
 *
 * Based on https://github.com/ChristianRiesen/base32/blob/master/src/Base32.php class
 */
enum Base32
{
    case Ascii;
    case Hex;

    private function alphabet(): string
    {
        return match ($this) {
            self::Ascii => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567=',
            self::Hex => '0123456789ABCDEFGHIJKLMNOPQRSTUV=',
        };
    }

    private function pattern(): string
    {
        return match ($this) {
            self::Ascii => '/[^A-Z2-7=]/',
            self::Hex => '/[^0-9A-V=]/',
        };
    }

    /**
     * @return array<array-key, int>
     */
    private function mapping(): array
    {
        return match ($this) {
            self::Ascii => [
                '=' => 0b00000,
                'A' => 0b00000,
                'B' => 0b00001,
                'C' => 0b00010,
                'D' => 0b00011,
                'E' => 0b00100,
                'F' => 0b00101,
                'G' => 0b00110,
                'H' => 0b00111,
                'I' => 0b01000,
                'J' => 0b01001,
                'K' => 0b01010,
                'L' => 0b01011,
                'M' => 0b01100,
                'N' => 0b01101,
                'O' => 0b01110,
                'P' => 0b01111,
                'Q' => 0b10000,
                'R' => 0b10001,
                'S' => 0b10010,
                'T' => 0b10011,
                'U' => 0b10100,
                'V' => 0b10101,
                'W' => 0b10110,
                'X' => 0b10111,
                'Y' => 0b11000,
                'Z' => 0b11001,
                '2' => 0b11010,
                '3' => 0b11011,
                '4' => 0b11100,
                '5' => 0b11101,
                '6' => 0b11110,
                '7' => 0b11111,
            ],
            self::Hex => [
                '=' => 0b00000,
                '0' => 0b00000,
                '1' => 0b00001,
                '2' => 0b00010,
                '3' => 0b00011,
                '4' => 0b00100,
                '5' => 0b00101,
                '6' => 0b00110,
                '7' => 0b00111,
                '8' => 0b01000,
                '9' => 0b01001,
                'A' => 0b01010,
                'B' => 0b01011,
                'C' => 0b01100,
                'D' => 0b01101,
                'E' => 0b01110,
                'F' => 0b01111,
                'G' => 0b10000,
                'H' => 0b10001,
                'I' => 0b10010,
                'J' => 0b10011,
                'K' => 0b10100,
                'L' => 0b10101,
                'M' => 0b10110,
                'N' => 0b10111,
                'O' => 0b11000,
                'P' => 0b11001,
                'Q' => 0b11010,
                'R' => 0b11011,
                'S' => 0b11100,
                'T' => 0b11101,
                'U' => 0b11110,
                'V' => 0b11111,
            ],
        };
    }

    public function encode(string $decoded): string
    {
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
        $alphabet = $this->alphabet();

        while ($n < $len || 0 !== $bitLen) {
            if ($bitLen < 5) {
                $val = $val << 8;
                $bitLen += 8;
                $n++;
                $val += $chars[$n];
            }
            $shift = $bitLen - 5;
            $encoded .= ($n - (int)($bitLen > 8) > $len && 0 == $val) ? '=' : $alphabet[$val >> $shift];
            $val = $val & ((1 << $shift) - 1);
            $bitLen -= 5;
        }

        return $encoded;
    }

    /**
     * @throws Base32Exception if the encoded string is invalid
     */
    public function decodeOrFail(string $encoded): string
    {
        if ('' === $encoded) {
            return '';
        }

        if (strtoupper($encoded) !== $encoded) {
            throw new Base32Exception('The encoded string contains lower-cased characters which is forbidden on strict mode.');
        }

        if (0 !== (strlen($encoded) % 8)) {
            throw new Base32Exception('The encoded string length is not a multiple of 8.');
        }

        if (str_contains(rtrim($encoded, '='), '=')) {
            throw new Base32Exception('A padding character is contained in the middle of the encoded string.');
        }

        if (1 !== preg_match('/^[^=]+((=){3,4}|(=){6}|=)?$/', $encoded)) {
            throw new Base32Exception('The encoded string contains an invalid padding length.');
        }

        if (1 === preg_match($this->pattern(), $encoded)) {
            throw new Base32Exception('The encoded string contains characters outside of the base32 '.(Base32::Hex === $this ? 'Extended Hex' : 'US-ASCII').' alphabet.');
        }

        return $this->decode($encoded);
    }

    public function decode(string $encoded): string
    {
        $encoded = strtoupper($encoded);
        $encoded = preg_replace($this->pattern(), '', $encoded);
        if ('' === $encoded || null === $encoded) {
            return '';
        }

        $decoded = '';
        $mapping = $this->mapping();
        $len = strlen($encoded);
        $n = 0;
        $bitLen = 5;
        $val = $mapping[$encoded[0]];

        while ($n < $len) {
            if ($bitLen < 8) {
                $val = $val << 5;
                $bitLen += 5;
                $n++;
                $pentet = $encoded[$n] ?? '=';
                if ('=' === $pentet) {
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

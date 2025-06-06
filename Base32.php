<?php

declare(strict_types=1);

namespace Bakame\Aide\Base32;

use RuntimeException;
use ValueError;

use function chr;
use function rtrim;
use function str_replace;
use function str_split;
use function strcspn;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;
use function unpack;

final class Base32
{
    private const ALPHABET_SIZE = 32;
    private const RESERVED_CHARACTERS = "\r\t\n ";
    /** @var non-empty-string */
    private readonly string $alphabet;
    /** @var non-empty-string */
    private readonly string $padding;

    /**
     * @param non-empty-string $alphabet
     * @param non-empty-string $padding
     */
    public function __construct(string $alphabet, string $padding)
    {
        if (1 !== strlen($padding) || false !== strpos(self::RESERVED_CHARACTERS, $padding)) {
            throw new ValueError('The padding character must be a non-reserved single byte character.');
        }

        if (self::ALPHABET_SIZE !== strlen($alphabet)) {
            throw new ValueError('The alphabet must be a '.self::ALPHABET_SIZE.' bytes long string.');
        }

        $upperAlphabet = strtoupper($alphabet);
        $upperPadding = strtoupper($padding);
        if (self::ALPHABET_SIZE !== strcspn($upperAlphabet, self::RESERVED_CHARACTERS.$upperPadding)) {
            throw new ValueError('The alphabet can not contain a reserved character.');
        }

        $uniqueChars = '';
        for ($index = 0; $index < self::ALPHABET_SIZE; $index++) {
            $char = $upperAlphabet[$index];
            if (false !== strpos($uniqueChars, $char)) {
                throw new ValueError('The alphabet must only contain unique characters.');
            }

            $uniqueChars .= $char;
        }

        $this->alphabet = $alphabet;
        $this->padding = $padding;
    }

    public function encode(string $decoded): string
    {
        if ('' === $decoded) {
            return '';
        }

        $offset = 0;
        $bitLen = 0;
        $val = 0;
        $length = strlen($decoded);
        $decoded .= chr(0).chr(0).chr(0).chr(0);
        $chars = (array) unpack('C*', $decoded);
        $encoded = '';
        while ($offset < $length || 0 !== $bitLen) {
            if ($bitLen < 5) {
                $bitLen += 8;
                $offset++;
                $val = ($val << 8) + $chars[$offset];
            }
            $shift = $bitLen - 5;
            $encoded .= ($offset - ($bitLen > 8 ? 1 : 0) > $length && 0 === $val) ? $this->padding : $this->alphabet[$val >> $shift];
            $val &= ((1 << $shift) - 1);
            $bitLen -= 5;
        }

        return $encoded;
    }

    public function decode(string $encoded, bool $strict = false): string
    {
        [$encoded, $alphabet, $padding] = $this->prepareDecoding($encoded, $strict);
        if ('' === $encoded) {
            return '';
        }

        $chars = [];
        for ($index = 0; $index < self::ALPHABET_SIZE; $index++) {
            $chars[$alphabet[$index]] = $index;
        }
        $chars[$padding] = 0;

        $offset = 0;
        $bitLen = 5;
        $length = strlen($encoded);
        $decoded = '';
        do {
            if (!isset($val)) {
                $index = $encoded[$offset];
                $val = $chars[$index] ?? -1;
            }

            if (-1 === $val) {
                if ($strict) {
                    throw new RuntimeException('The encoded data contains characters unknown to the alphabet.');
                }
                $offset++;
                if ($offset < $length) {
                    $val = null;
                    continue;
                }
                break;
            }

            if ($bitLen < 8) {
                $bitLen += 5;
                $offset++;
                $pentet = $encoded[$offset] ?? $padding;
                if ($padding === $pentet) {
                    $offset = $length;
                }

                if ($strict && !isset($chars[$pentet])) {
                    throw new RuntimeException('The encoded data contains characters unknown to the alphabet.');
                }

                $val = ($val << 5) + ($chars[$pentet] ?? 0);
                continue;
            }

            $shift = $bitLen - 8;
            $decoded .= chr($val >> $shift);
            $val &= ((1 << $shift) - 1);
            $bitLen -= 8;
        } while ($offset < $length);

        return $decoded;
    }

    /**
     * @return array<string>
     */
    private function prepareDecoding(string $encoded, bool $strict): array
    {
        if ('' === $encoded) {
            return ['', $this->alphabet, $this->padding];
        }

        $alphabet = $this->alphabet;
        $padding = $this->padding;
        $encoded = str_replace(str_split(self::RESERVED_CHARACTERS), [''], $encoded);
        if (!$strict) {
            $alphabet = strtoupper($alphabet);
            $padding = strtoupper($padding);
            $encoded = strtoupper($encoded);
        }

        $inside = rtrim($encoded, $padding);
        $end = substr($encoded, strlen($inside));
        if ($strict) {
            $endLength = strlen($end);
            if (0 !== $endLength && 1 !== $endLength && 3 !== $endLength && 4 !== $endLength && 6 !== $endLength) {
                throw new RuntimeException('The encoded data ends with an invalid padding sequence length.');
            }
        }

        if (false !== strpos($inside, $padding)) {
            if ($strict) {
                throw new RuntimeException('The padding character is used in the encoded data in an invalid place.');
            }

            $encoded = str_replace($padding, '', $inside).$end;
        }

        $remainder = strlen($encoded) % 8;
        if (0 !== $remainder) {
            if ($strict) {
                throw new RuntimeException('The encoded data length is invalid.');
            }

            $remainderStr = '';
            for ($index = 0; $index < $remainder; $index++) {
                $remainderStr .= $padding;
            }

            $encoded .= $remainderStr;
        }

        return [$encoded, $alphabet, $padding];
    }
}

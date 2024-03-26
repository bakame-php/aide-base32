<?php

declare(strict_types=1);

namespace Bakame\Aide\Base32;

use RuntimeException;
use ValueError;

final class Base32
{
    private const ALPHABET_SIZE = 32;
    private const RESERVED_CHARACTERS = "\r\n ";
    /** @var non-empty-string */
    private readonly string $alphabet;
    /** @var non-empty-string */
    private readonly string $padding;

    /**
     * @param non-empty-string $alphabet
     * @param non-empty-string $padding
     */
    private function __construct(string $alphabet, string $padding)
    {
        if (1 !== strlen($padding) || false !== strpos(self::RESERVED_CHARACTERS, $padding)) {
            throw new ValueError('The padding character must be a non reserved single byte character.');
        }

        if (self::ALPHABET_SIZE !== strlen($alphabet)) {
            throw new ValueError('The alphabet must be a '.self::ALPHABET_SIZE.' bytes long string.');
        }

        $upperAlphabet = strtoupper($alphabet);
        $upperPadding = strtoupper($padding);
        if (32 !== strcspn($upperAlphabet, self::RESERVED_CHARACTERS.$upperPadding)) {
            throw new ValueError('The alphabet can not contain a reserved character.');
        }

        $uniqueChars = [];
        for ($index = 0; $index < self::ALPHABET_SIZE; $index++) {
            $char = $upperAlphabet[$index];
            if (array_key_exists($char, $uniqueChars)) {
                throw new ValueError('The alphabet must only contain unique characters.');
            }

            $uniqueChars[$char] = 1;
        }

        $this->alphabet = $alphabet;
        $this->padding = $padding;
    }

    /**
     * @param non-empty-string $alphabet
     * @param non-empty-string $padding
     */
    public static function new(string $alphabet, string $padding): self
    {
        return new self($alphabet, $padding);
    }

    public function decode(string $encoded, bool $strict = false): string
    {
        if ('' === $encoded) {
            return '';
        }

        $alphabet = $this->alphabet;
        $padding = $this->padding;
        $encoded = str_replace(str_split(self::RESERVED_CHARACTERS), [''], $encoded);
        if (!$strict) {
            $alphabet = strtoupper($alphabet);
            $padding = strtoupper($padding);
            $encoded = strtoupper($encoded);
        }

        $remainder = strlen($encoded) % 8;
        if (0 !== $remainder) {
            if ($strict) {
                throw new RuntimeException('The encoded data length is invalid.');
            }

            $encoded .= str_repeat($padding, $remainder);
        }

        $inside = rtrim($encoded, $padding);
        $end = substr($encoded, strlen($inside));
        $endLength = strlen($end);
        if ($strict && 0 !== $endLength && 1 !== $endLength && 3 !== $endLength && 4 !== $endLength && 6 !== $endLength) {
            throw new RuntimeException('The encoded data ends with an invalid padding sequence length.');
        }

        if (false !== strpos($inside, $padding)) {
            if ($strict) {
                throw new RuntimeException('The padding character is used in the encoded data in an invalid place.');
            }

            $encoded = str_replace($padding, '', $inside).$end;
        }

        $chars = [];
        foreach (str_split($alphabet) as $offset => $char) {
            $chars[$char] = $offset;
        }
        $chars[$padding] = 0;
        $offset = 0;
        $bitLen = 5;
        $length = strlen($encoded);
        $decoded = '';

        do {
            $val ??= $chars[$encoded[$offset]] ?? -1;
            if (-1 === $val) {
                if ($strict) {
                    throw new RuntimeException('The encoded data contains characters unknown to the base32 alphabet.');
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

                if ($strict && !array_key_exists($pentet, $chars)) {
                    throw new RuntimeException('The encoded data contains characters unknown to the base32 alphabet.');
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

    public function encode(string $decoded): string
    {
        if ('' === $decoded) {
            return '';
        }

        $offset = 0;
        $bitLen = 0;
        $val = 0;
        $length = strlen($decoded);
        $decoded .= str_repeat(chr(0), 4);
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
}

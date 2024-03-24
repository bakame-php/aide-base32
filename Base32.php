<?php

declare(strict_types=1);

namespace Bakame\Aide\Base32;

use RuntimeException;
use ValueError;

final class Base32
{
    private const ALPHABET_SIZE = 32;
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
        $normalizeAlphabet = strtoupper($alphabet);
        [$this->alphabet, $this->padding] = match (true) {
            1 !== strlen($padding) => throw new ValueError('The padding character must a single character.'),
            "\r" === $padding => throw new ValueError('The padding character can not be the carriage return character.'),
            "\n" === $padding => throw new ValueError('The padding character can not be the newline escape sequence.'),
            ' ' === $padding => throw new ValueError('The padding character can not be the space character.'),
            self::ALPHABET_SIZE !== strlen($alphabet) => throw new ValueError('The alphabet must be a 32 bytes long string.'),
            self::ALPHABET_SIZE !== strlen(count_chars($normalizeAlphabet, 3)) => throw new ValueError('The alphabet must contain omly unique characters.'), /* @phpstan-ignore-line */
            str_contains($alphabet, "\r") => throw new ValueError('The alphabet can not contain the carriage return character.'),
            str_contains($alphabet, "\n") => throw new ValueError('The alphabet can not contain the newline escape sequence.'),
            str_contains($alphabet, " ") => throw new ValueError('The alphabet can not contain the space character.'),
            str_contains($normalizeAlphabet, strtoupper($padding)) => throw new ValueError('The alphabet can not contain the padding character.'),
            default => [$alphabet, $padding],
        };
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
        $encoded = str_replace(["\r", "\n", ' '], [''], $encoded);
        if (!$strict) {
            $alphabet = strtoupper($alphabet);
            $padding = strtoupper($padding);
            $encoded = strtoupper($encoded);
        }

        $remainder = strlen($encoded) % 8;
        if (0 !== $remainder) {
            $encoded .= !$strict ?
                str_repeat($padding, $remainder) :
                throw new RuntimeException('The encoded data length is invalid.');
        }

        $inside = rtrim($encoded, $padding);
        $end = substr($encoded, strlen($inside));
        if ($strict && !in_array(strlen($end), [0, 1, 3, 4, 6], true)) {
            throw new RuntimeException('The encoded data ends with an invalid padding sequence length.');
        }

        if (str_contains($inside, $padding)) {
            $encoded = !$strict ?
                str_replace($padding, '', $inside).$end :
                throw new RuntimeException('The padding character is used inside the encoded data in an invalid place.');
        }

        $characters = $alphabet.$padding;
        $decoded = '';
        $offset = 0;
        $bitLen = 5;
        $length = strlen($encoded);
        $chars = array_combine(str_split($characters), [...range(0, 31), 0]);
        $val = $chars[$encoded[$offset]] ?? -1;
        if ($strict && -1 === $val) {
            throw new RuntimeException('The encoded data contains characters unknown to the base32 alphabet.');
        }

        while ($offset < $length) {
            if (-1 === $val) {
                if ($strict) {
                    throw new RuntimeException('The encoded data contains characters unknown to the base32 alphabet.');
                }
                $offset++;
                if ($offset === $length) {
                    break;
                }
                $val = $chars[$encoded[$offset]] ?? -1;
                continue;
            }

            if ($bitLen < 8) {
                $bitLen += 5;
                $offset++;
                $pentet = $encoded[$offset] ?? $padding;
                if ($padding === $pentet) {
                    $offset = $length;
                }

                if (!array_key_exists($pentet, $chars) && $strict) {
                    throw new RuntimeException('The encoded data contains characters unknown to the base32 alphabet.');
                }

                $val = ($val << 5) + ($chars[$pentet] ?? 0);
                continue;
            }

            $shift = $bitLen - 8;
            $decoded .= chr($val >> $shift);
            $val &= ((1 << $shift) - 1);
            $bitLen -= 8;
        }

        return $decoded;
    }

    public function encode(string $decoded): string
    {
        if ('' === $decoded) {
            return '';
        }

        $encoded = '';
        $offset = 0;
        $bitLen = 0;
        $val = 0;
        $length = strlen($decoded);
        $decoded .= str_repeat(chr(0), 4);
        $chars = (array) unpack('C*', $decoded);
        $characters = $this->alphabet.$this->padding;

        while ($offset < $length || 0 !== $bitLen) {
            if ($bitLen < 5) {
                $bitLen += 8;
                $offset++;
                $val = ($val << 8) + $chars[$offset];
            }
            $shift = $bitLen - 5;
            $encoded .= ($offset - (int)($bitLen > 8) > $length && 0 == $val) ? $this->padding : $characters[$val >> $shift];
            $val &= ((1 << $shift) - 1);
            $bitLen -= 5;
        }

        return $encoded;
    }
}

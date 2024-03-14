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
            self::ALPHABET_SIZE !== strlen($alphabet) => throw new ValueError('The alphabet must be a 32 bytes long string.'),
            str_contains($alphabet, "\r") => throw new ValueError('The alphabet can not contain the carriage return character.'),
            str_contains($alphabet, "\n") => throw new ValueError('The alphabet can not contain the newline escape sequence.'),
            str_contains($normalizeAlphabet, strtoupper($padding)) => throw new ValueError('The alphabet can not contain the padding character.'),
            self::ALPHABET_SIZE !== count(array_unique(str_split($normalizeAlphabet))) => throw new ValueError('The alphabet must contain unique characters.'),
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
        $encoded = str_replace(["\r", "\n"], [''], $encoded);
        if (!$strict) {
            $alphabet = strtoupper($alphabet);
            $encoded = str_replace(strtoupper($this->padding), $this->padding, strtoupper($encoded));
        }

        $remainder = strlen($encoded) % 8;
        if (0 !== $remainder) {
            if ($strict) {
                throw new RuntimeException('The encoded data length is invalid.');
            }

            $encoded .= str_repeat($this->padding, $remainder);
        }

        $characters = $alphabet.$this->padding;
        if (strspn($encoded, $characters) !== strlen($encoded)) {
            if ($strict) {
                throw new RuntimeException('The encoded data contains characters unknown to the alphabet.');
            }
            $encoded = preg_replace('/[^'.preg_quote($characters, '/').']/', '', $encoded);
            if ('' === $encoded || null === $encoded) {
                return '';
            }
        }

        $inside = rtrim($encoded, $this->padding);
        if (str_contains($inside, $this->padding)) {
            if ($strict) {
                throw new RuntimeException('The encoded data contains the padding character.');
            }
            $encoded = str_replace($this->padding, '', $inside).substr($encoded, strlen($inside));
        }

        if ($strict && 1 !== preg_match('/^[^'.$this->padding.']+(('.$this->padding.'){3,4}|('.$this->padding.'){6}|'.$this->padding.')?$/', $encoded)) {
            throw new RuntimeException('The encoded data contains the padding character.');
        }

        $decoded = '';
        $offset = 0;
        $bitLen = 5;
        $length = strlen($encoded);
        $chars = array_combine(str_split($characters), [...range(0, 31), 0]);
        $val = $chars[$encoded[0]];

        while ($offset < $length) {
            if ($bitLen < 8) {
                $bitLen += 5;
                $offset++;
                $pentet = $encoded[$offset] ?? $this->padding;
                if ($this->padding === $pentet) {
                    $offset = $length;
                }
                $val = ($val << 5) + $chars[$pentet];
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

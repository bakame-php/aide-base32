<?php

declare(strict_types=1);

namespace Bakame\Aide\Base32;

use RuntimeException;
use ValueError;

final class Base32
{
    private readonly string $alphabet;
    private readonly string $padding;

    private function __construct(string $alphabet, string $padding)
    {
        $normalizeAlphabet = strtoupper($alphabet);

        [$this->alphabet, $this->padding] = match (true) {
            1 !== strlen($padding) => throw new ValueError('The padding character must a single character.'),
            "\r" === $padding => throw new ValueError('The padding character can not be the carriage return character.'),
            "\n" === $padding => throw new ValueError('The padding character can not be the newline escape sequence.'),
            32 !== strlen($alphabet) => throw new ValueError('The alphabet must be a 32 bytes long string.'),
            str_contains($alphabet, "\r") => throw new ValueError('The alphabet can not contain the carriage return character.'),
            str_contains($alphabet, "\n") => throw new ValueError('The alphabet can not contain the newline escape sequence.'),
            32 !== count(array_unique(str_split($normalizeAlphabet))) => throw new ValueError('The alphabet must contain unique characters.'),
            str_contains($normalizeAlphabet, strtoupper($padding)) => throw new ValueError('The alphabet can not contain the padding character.'),
            default => [$alphabet, $padding],
        };
    }
""
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

        $characters = $this->alphabet.$this->padding;
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
                throw new RuntimeException('The encoded data contains the padding characters.');
            }
            $encoded = str_replace($this->padding, '', $inside).substr($encoded, strlen($inside));
        }

        if ($strict && 1 !== preg_match('/^[^'.$this->padding.']+(('.$this->padding.'){3,4}|('.$this->padding.'){6}|'.$this->padding.')?$/', $encoded)) {
            throw new RuntimeException('The encoded data contains the padding characters.');
        }

        $decoded = '';
        $len = strlen($encoded);
        $n = 0;
        $bitLen = 5;
        $mapping = array_combine(str_split($characters), [...range(0, 31), 0]);
        $val = $mapping[$encoded[0]];

        while ($n < $len) {
            if ($bitLen < 8) {
                $val = $val << 5;
                $bitLen += 5;
                $n++;
                $pentet = $encoded[$n] ?? $this->padding;
                if ($this->padding === $pentet) {
                    $n = $len;
                }
                $val += $mapping[$pentet];
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
        $n = 0;
        $bitLen = 0;
        $val = 0;
        $len = strlen($decoded);
        $decoded .= str_repeat(chr(0), 4);
        $chars = (array) unpack('C*', $decoded);
        $characters = $this->alphabet.$this->padding;

        while ($n < $len || 0 !== $bitLen) {
            if ($bitLen < 5) {
                $val = $val << 8;
                $bitLen += 8;
                $n++;
                $val += $chars[$n];
            }
            $shift = $bitLen - 5;
            $encoded .= ($n - (int)($bitLen > 8) > $len && 0 == $val) ? $this->padding : $characters[$val >> $shift];
            $val &= ((1 << $shift) - 1);
            $bitLen -= 5;
        }

        return $encoded;
    }
}

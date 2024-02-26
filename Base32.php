<?php

declare(strict_types=1);

namespace Bakame\Aide\Base32;

use RuntimeException;
use ValueError;

final class Base32
{
    private function __construct(
        private readonly string $alphabet,
        private readonly string $padding,
    ) {
        if (in_array($this->padding, ["\r", "\n"], true)) {
            throw new ValueError('The padding character is invalid.');
        }

        if (1 !== strlen($this->padding)) {
            throw new ValueError('The padding character must be one byte long.');
        }

        if (32 !== count(array_unique(str_split($alphabet)))) {
            throw new ValueError('The alphabet must be 32 bytes long containing unique characters.');
        }

        if (
            str_contains($this->alphabet, "\r") ||
            str_contains($this->alphabet, "\n") ||
            str_contains($this->alphabet, $this->padding)
        ) {
            throw new ValueError('The alphabet contains invalid characters.');
        }
    }

    public static function new(string $alphabet, string $padding): self
    {
        return new self($alphabet, $padding);
    }

    public function decode(string $encoded, bool $strict = false): string
    {
        if ('' === $encoded) {
            return '';
        }

        if (!$strict) {
            $encoded = strtoupper($encoded);
        }

        if (strtoupper($encoded) !== $encoded) {
            throw new RuntimeException('The encoded data contains non uppercased characters.');
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
            $val = $val & ((1 << $shift) - 1);
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
        $alphabet = $this->alphabet.$this->padding;

        while ($n < $len || 0 !== $bitLen) {
            if ($bitLen < 5) {
                $val = $val << 8;
                $bitLen += 8;
                $n++;
                $val += $chars[$n];
            }
            $shift = $bitLen - 5;
            $encoded .= ($n - (int)($bitLen > 8) > $len && 0 == $val) ? $this->padding : $alphabet[$val >> $shift];
            $val = $val & ((1 << $shift) - 1);
            $bitLen -= 5;
        }

        return $encoded;
    }
}

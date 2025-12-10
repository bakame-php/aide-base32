<?php

declare(strict_types=1);

namespace Encoding {

    use ValueError;

    use function array_fill;
    use function chr;
    use function function_exists;
    use function in_array;
    use function ord;
    use function rtrim;
    use function str_repeat;
    use function str_replace;
    use function strlen;
    use function strtoupper;
    use function substr;
    use function unpack;

    if (!function_exists('Encoding\base32_encode')) {
        function base32_encode(
            string $data,
            Base32 $variant = Base32::Ascii,
            PaddingMode $paddingMode = PaddingMode::VariantControlled,
        ): string {
            if ('' === $data) {
                return '';
            }

            $padding = match ($variant) {
                Base32::Ascii,
                Base32::Hex => '=',
                Base32::Z,
                Base32::Crockford => '',
            };

            '' !== $padding || PaddingMode::PreservePadding !== $paddingMode || throw new ValueError('The variant does not allow padding usage.');

            $alphabet = match ($variant) {
                Base32::Ascii => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',
                Base32::Hex => '0123456789ABCDEFGHIJKLMNOPQRSTUV',
                Base32::Crockford => '0123456789ABCDEFGHJKMNPQRSTVWXYZ',
                Base32::Z => 'ybndrfg8ejkmcpqxot1uwisza345h769',
            };
            $offset = 0;
            $bitLen = 0;
            $val = 0;
            $length = strlen($data);
            $data .= chr(0).chr(0).chr(0).chr(0);
            $chars = (array)unpack('C*', $data);
            $encoded = '';
            while ($offset < $length || 0 !== $bitLen) {
                if ($bitLen < 5) {
                    $bitLen += 8;
                    $offset++;
                    $val = ($val << 8) + $chars[$offset]; /* @phpstan-ignore-line */
                }
                $shift = $bitLen - 5;
                $encoded .= ($offset - ($bitLen > 8 ? 1 : 0) > $length && 0 === $val) ? $padding : $alphabet[$val >> $shift];
                $val &= ((1 << $shift) - 1);
                $bitLen -= 5;
            }

            return PaddingMode::StripPadding === $paddingMode ? rtrim($encoded, $padding) : $encoded;
        }
    }

    if (!function_exists('Encoding\base32_decode')) {
        function base32_decode(
            string $data,
            Base32 $variant = Base32::Ascii,
            DecodingMode $decodingMode = DecodingMode::Strict
        ): string {
            /**
             * @throws ValueError|UnableToDecodeException
             *
             * @return list<string>
             */
            $prepareDecoding = function (string $data, Base32 $variant, DecodingMode $decodingMode): array {
                $padding = match ($variant) {
                    Base32::Ascii,
                    Base32::Hex => '=',
                    Base32::Z,
                    Base32::Crockford => '',
                };

                $alphabet = match ($variant) {
                    Base32::Ascii => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',
                    Base32::Hex => '0123456789ABCDEFGHIJKLMNOPQRSTUV',
                    Base32::Crockford => '0123456789ABCDEFGHJKMNPQRSTVWXYZ',
                    Base32::Z => 'ybndrfg8ejkmcpqxot1uwisza345h769',
                };

                if ('' === $data) {
                    return ['', $alphabet, $padding];
                }

                $data = str_replace(["\r", "\n", "\t", ' '], [''], $data);
                if (DecodingMode::Forgiving === $decodingMode) {
                    $alphabet = strtoupper($alphabet);
                    $data = strtoupper($data);
                }

                $inside = rtrim($data, $padding);
                $end = substr($data, strlen($inside));
                DecodingMode::Strict !== $decodingMode || in_array(strlen($end), [0, 1, 3, 4, 6], true) || throw new UnableToDecodeException('The encoded data ends with an invalid padding sequence length.');

                if (str_contains($inside, $padding)) {
                    DecodingMode::Strict !== $decodingMode || throw new UnableToDecodeException('The padding character is used in the encoded data in an invalid place.');

                    $data = str_replace($padding, '', $inside).$end;
                }

                $remainder = strlen($data) % 8;
                if (0 !== $remainder) {
                    DecodingMode::Strict !== $decodingMode || throw new UnableToDecodeException('The encoded data length is invalid.');
                    $remainderStr = '';
                    for ($index = 0; $index < $remainder; $index++) {
                        $remainderStr .= $padding;
                    }

                    $data .= $remainderStr;
                }

                return [$data, $alphabet, $padding];
            };

            [$data, $alphabet, $padding] = $prepareDecoding($data, $variant, $decodingMode);
            if ('' === $data) {
                return '';
            }

            $chars = [];
            for ($index = 0; $index < 32; $index++) {
                $chars[$alphabet[$index]] = $index;
            }
            $chars[$padding] = 0;

            $offset = 0;
            $bitLen = 5;
            $length = strlen($data);
            $decoded = '';
            do {
                if (!isset($val)) {
                    $index = $data[$offset];
                    $val = $chars[$index] ?? -1;
                }

                if (-1 === $val) {
                    DecodingMode::Strict !== $decodingMode || throw new UnableToDecodeException('The encoded data contains characters unknown to the alphabet.');
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
                    $pentet = $data[$offset] ?? $padding;
                    if ($padding === $pentet) {
                        $offset = $length;
                    }

                    DecodingMode::Strict !== $decodingMode || isset($chars[$pentet]) || throw new UnableToDecodeException('The encoded data contains characters unknown to the alphabet.');

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
    }

    if (!function_exists('Encoding\base16_encode')) {
        function base16_encode(
            string $data,
            Base16 $variant = Base16::Upper
        ): string {
            if ('' === $data) {
                return '';
            }

            $encoded = '';
            $chars = Base16::Lower === $variant ? '0123456789abcdef' : '0123456789ABCDEF';
            for ($i = 0, $len = strlen($data); $i < $len; $i++) {
                $byte = ord($data[$i]);
                $encoded .= $chars[$byte >> 4].$chars[$byte & 0x0F];
            }

            return $encoded;
        }
    }

    if (!function_exists('Encoding\base16_decode')) {
        function base16_decode(
            string $data,
            Base16 $variant = Base16::Upper,
            DecodingMode $decodingMode = DecodingMode::Strict,
            TimingMode $timingMode = TimingMode::Constant,
        ): string {
            $len = strlen($data);
            if (0 === $len) {
                return '';
            }

            $isConstantTime = TimingMode::Constant === $timingMode;
            /** @var ?string $skipLut */
            static $skipLut = null;
            if (null === $skipLut) {
                $skipLut = array_fill(0, 256, 0);
                foreach (["\r", "\n", "\t", ' '] as $char) {
                    $skipLut[ord($char)] = 1;
                }
            }

            $compact = str_repeat("\0", $len);
            $clen = 0;
            for ($i = 0; $i < $len; $i++) {
                $ch = $data[$i];
                $compact[$clen] = $ch;
                $clen += (int)(0 === $skipLut[ord($ch)]);
            }

            $compact = substr($compact, 0, $clen);
            $valid = 1 & (int)(($clen & 1) === 0);
            if (0 !== ($clen & 1)) {
                $compact .= "\0";
                $valid = 0;
            }

            $isConstantTime || 0 !== $valid || throw new UnableToDecodeException('The data could not be decoded.');

            /** @var string $uppercase */
            static $uppercase = '0123456789ABCDEF';
            /** @var string $lowercase */
            static $lowercase = '0123456789abcdef';

            $hex = (Base16::Upper === $variant) ? $uppercase : $lowercase;
            $lut = array_fill(0, 256, 255);
            if (DecodingMode::Strict === $decodingMode) {
                for ($i = 0; $i < 16; $i++) {
                    $lut[ord($hex[$i])] = $i;
                }
            } else {
                for ($i = 0; $i < 16; $i++) {
                    $lut[ord($uppercase[$i])] = $i;
                    $lut[ord($lowercase[$i])] = $i;
                }
            }

            $decoded = str_repeat("\0", $clen >> 1);
            for ($i = 0, $j = 0; $i < $clen; $i += 2, $j++) {
                $v1 = $lut[ord($compact[$i])];
                $v2 = $lut[ord($compact[$i + 1])];

                $valid = $valid & (int)(255 !== $v1) & (int)(255 !== $v2);
                $isConstantTime || 0 !== $valid || throw new UnableToDecodeException('The data could not be decoded.');
                $decoded[$j] = chr(($v1 << 4) | $v2);
            }

            return 0 !== $valid ? $decoded : throw new UnableToDecodeException('The data could not be decoded.');
        }
    }
}

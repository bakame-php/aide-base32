<?php

declare(strict_types=1);

namespace Bakame\Aide\Base32;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function base64_decode;

use const PHP_BASE32_ASCII;
use const PHP_BASE32_HEX;

final class Base32Test extends TestCase
{
    /**
     * Strings to test back and forth encoding/decoding to make sure results are the same.
     *
     * @var array<string,string>
     */
    private const BASE_CLEAR_STRINGS = [
        'Empty String' => [''],
        'Ten' => ['10'],
        'Test130' => ['test130'],
        'test' => ['test'],
        'Eight' => ['8'],
        'Zero' => ['0'],
        'Equals' => ['='],
        'Foobar' => ['foobar'],
    ];

    /**
     * Vectors from RFC with cleartext => base32 pairs.
     *
     * @var array<string,string>
     */
    private const RFC_VECTORS = [
        'ASCII' => [
            'RFC Vector 1' => ['f', 'MY======'],
            'RFC Vector 2' => ['fo', 'MZXQ===='],
            'RFC Vector 3' => ['foo', 'MZXW6==='],
            'RFC Vector 4' => ['foob', 'MZXW6YQ='],
            'RFC Vector 5' => ['fooba', 'MZXW6YTB'],
            'RFC Vector 6' => ['foobar', 'MZXW6YTBOI======'],
            'Old Vector 1' => [' ', 'EA======'],
            'Old Vector 2' => ['  ', 'EAQA===='],
            'Old Vector 3' => ['   ', 'EAQCA==='],
            'Old Vector 4' => ['    ', 'EAQCAIA='],
            'Old Vector 5' => ['     ', 'EAQCAIBA'],
            'Old Vector 6' => ['      ', 'EAQCAIBAEA======'],
        ],
        'HEX' => [
            'RFC Vector 1' => ['f', 'CO======'],
            'RFC Vector 2' => ['fo', 'CPNG===='],
            'RFC Vector 3' => ['foo', 'CPNMU==='],
            'RFC Vector 4' => ['foob', 'CPNMUOG='],
            'RFC Vector 5' => ['fooba', 'CPNMUOJ1'],
            'RFC Vector 6' => ['foobar', 'CPNMUOJ1E8======'],
        ],
    ];

    /**
     * @return array<string, array>
     */
    public static function base32decodeAsciiDataProvider(): array
    {
        $decodedData = [
            'Empty String' => ['', ''],
            'All Invalid Characters' => ['', '8908908908908908'],
            'Random Integers' => [base64_decode('HgxBl1kJ4souh+ELRIHm/x8yTc/cgjDmiCNyJR/NJfs='), 'DYGEDF2ZBHRMULUH4EFUJAPG74PTETOP3SBDBZUIENZCKH6NEX5Q===='],
            'Partial zero edge case' => ['8', 'HA======'],
        ];

        return [...$decodedData, ...self::RFC_VECTORS['ASCII']];
    }

    /**
     * @return array<string, array>
     */
    public static function base32encodeAsciiDataProvider(): array
    {
        $encodeData = [
            'Empty String' => ['', ''],
            'Random Integers' => [base64_decode('HgxBl1kJ4souh+ELRIHm/x8yTc/cgjDmiCNyJR/NJfs='), 'DYGEDF2ZBHRMULUH4EFUJAPG74PTETOP3SBDBZUIENZCKH6NEX5Q===='],
            'Partial zero edge case' => ['8', 'HA======'],
        ];

        return [...$encodeData, ...self::RFC_VECTORS['ASCII']];
    }

    /**
     * Back and forth encoding must return the same result.
     *
     * @return array<string, array>
     */
    public static function backAndForthDataProvider(): array
    {
        return self::BASE_CLEAR_STRINGS;
    }

    /**
     * @return array<string, array>
     */
    public static function base32decodeHexDataProvider(): array
    {
        $decodedData = [
            'Empty String' => ['', ''],
            'All Invalid Characters' => ['', 'WXYXWXYZWXYZWXYZ'],
            'Random Integers' => [base64_decode('HgxBl1kJ4souh+ELRIHm/x8yTc/cgjDmiCNyJR/NJfs='), '3O6435QP17HCKBK7S45K90F6VSFJ4JEFRI131PK84DP2A7UD4NTG===='],
        ];

        return [...$decodedData, ...self::RFC_VECTORS['HEX']];
    }

    /**
     * @return array<string, array>
     */
    public static function base32encodeHexDataProvider(): array
    {
        $encodeData = [
            'Empty String' => ['', ''],
            'Random Integers' => [base64_decode('HgxBl1kJ4souh+ELRIHm/x8yTc/cgjDmiCNyJR/NJfs='), '3O6435QP17HCKBK7S45K90F6VSFJ4JEFRI131PK84DP2A7UD4NTG===='],
        ];

        return [...$encodeData, ...self::RFC_VECTORS['HEX']];
    }

    #[DataProvider('base32decodeAsciiDataProvider')]
    #[Test]
    public function it_will_base32_decode_on_ascii_mode(string $clear, string $base32): void
    {
        self::assertEquals($clear, base32_decode($base32));
    }

    #[DataProvider('base32encodeAsciiDataProvider')]
    #[Test]
    public function it_will_base32_encode_on_ascii_mode(string $clear, string $base32): void
    {
        self::assertEquals($base32, base32_encode($clear));
    }

    #[DataProvider('base32decodeHexDataProvider')]
    #[Test]
    public function it_will_base32_decode_on_hex_mode(string $clear, string $base32): void
    {
        self::assertEquals($clear, base32_decode($base32, PHP_BASE32_HEX));
        self::assertEquals($clear, base32_decode($base32, PHP_BASE32_HEX, true));
    }

    #[DataProvider('base32encodeHexDataProvider')]
    #[Test]
    public function it_will_base32_encode_on_hex_mode(string $clear, string $base32): void
    {
        self::assertEquals($base32, base32_encode($clear, PHP_BASE32_HEX));
    }

    #[DataProvider('backAndForthDataProvider')]
    #[Test]
    public function it_will_base32_encode_and_decode(string $clear): void
    {
        self::assertEquals($clear, base32_decode(base32_encode($clear)));
        self::assertEquals($clear, base32_decode(base32_encode($clear, PHP_BASE32_HEX), PHP_BASE32_HEX));
    }

    #[DataProvider('invalidDecodingSequence')]
    #[Test]
    public function it_will_throw_on_strict_mode_with_invalid_encoded_string_on_decode(string $sequence, string $message, int $encoding): void
    {
        $this->expectException(Base32Exception::class);
        $this->expectExceptionMessage($message);

        match ($encoding) {
            PHP_BASE32_HEX => Base32::Hex->decodeOrFail($sequence),
            default => Base32::Ascii->decodeOrFail($sequence),
        };
    }

    #[DataProvider('invalidDecodingSequence')]
    #[Test]
    public function it_will_return_false_from_invalid_encoded_string_with_base32_decode_function(string $sequence, string $message, int $encoding): void
    {
        self::assertFalse(base32_decode($sequence, $encoding, true));
    }

    /**
     * @return iterable<string, array{sequence: string, message: string, sequence: int<1, 2>}>
     */
    public static function invalidDecodingSequence(): iterable
    {
        yield 'characters outside of base32 extended hex alphabet' => [
            'sequence' => 'MZXQ====',
            'message' => 'The encoded string contains characters outside of the base32 Extended Hex alphabet.',
            'encoding' => PHP_BASE32_HEX,
        ];

        yield 'characters outside of base32 us ascii alphabet' => [
            'sequence' => '90890808',
            'message' => 'The encoded string contains characters outside of the base32 US-ASCII alphabet.',
            'encoding' => PHP_BASE32_ASCII,
        ];

        yield 'characters not upper-cased' => [
            'sequence' => 'MzxQ====',
            'message' => 'The encoded string contains lower-cased characters which is forbidden on strict mode.',
            'encoding' => PHP_BASE32_ASCII,
        ];

        yield 'padding character in the middle of the sequence' => [
            'sequence' => 'A=ACA===',
            'message' => 'A padding character is contained in the middle of the encoded string.',
            'encoding' => PHP_BASE32_ASCII,
        ];

        yield 'invalid padding length' => [
            'sequence' => 'A=======',
            'message' => 'The encoded string contains an invalid padding length.',
            'encoding' => PHP_BASE32_ASCII,
        ];

        yield 'invalid encoded string length' => [
            'sequence' => 'A',
            'message' => 'The encoded string length is not a multiple of 8.',
            'encoding' => PHP_BASE32_HEX,
        ];
    }
}

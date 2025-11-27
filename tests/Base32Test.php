<?php

declare(strict_types=1);

use Encoding\Base32;
use Encoding\UnableToDecodeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Encoding\base32_encode;
use function Encoding\base32_decode;

/**
 * @see https://opensource.apple.com/source/tcl/tcl-87/tcl_ext/tcllib/tcllib/modules/base32/base32hex.testsuite.auto.html
 * @see https://opensource.apple.com/source/tcl/tcl-87/tcl_ext/tcllib/tcllib/modules/base32/base32.testsuite.auto.html
 */
final class Base32Test extends TestCase
{
    #[DataProvider('base32encodeAsciiDataProvider')]
    #[Test]
    public function it_will_base32_encode_on_ascii_mode(string $decoded, string $encoded): void
    {
        self::assertSame($encoded, base32_encode($decoded));
    }

    #[DataProvider('base32encodeHexDataProvider')]
    #[Test]
    public function it_will_base32_encode_on_hex_mode(string $decoded, string $encoded): void
    {
        self::assertSame($encoded, base32_encode($decoded, Base32::Hex));
    }

    #[DataProvider('base32decodeAsciiDataProvider')]
    #[Test]
    public function it_will_base32_decode_on_ascii_mode(string $decoded, string $encoded): void
    {
        self::assertSame($decoded, base32_decode($encoded));
    }

    #[DataProvider('base32decodeHexDataProvider')]
    #[Test]
    public function it_will_base32_decode_on_hex_mode(string $decoded, string $encoded): void
    {
        self::assertSame($decoded, base32_decode($encoded, Base32::Hex));
    }

    #[DataProvider('backAndForthDataProvider')]
    #[Test]
    public function it_will_base32_encode_and_decode(string $string): void
    {
        self::assertSame($string, base32_decode(base32_encode($string)));
        self::assertSame($string, base32_decode(base32_encode($string, Base32::Hex), Base32::Hex));
    }

    #[Test]
    public function it_will_base32_decode_multiline_data(): void
    {
        $base32 = <<<BASE
89GMSPRL
D4======
BASE;
        self::assertSame('Bangui', base32_decode($base32, Base32::Hex));
    }

    #[DataProvider('invalidDecodingSequence')]
    #[Test]
    public function it_will_return_false_from_invalid_encoded_string_with_base32_decode_function(
        string $sequence,
        Base32 $alphabet,
    ): void {
        $this->expectException(UnableToDecodeException::class);

        base32_decode($sequence, $alphabet);
    }

    /**
     * @return array<string, array{0:string|false, 1:string}>
     */
    public static function base32encodeAsciiDataProvider(): array
    {
        return [
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
            'Empty String' => ['', ''],
            'Random Integers' => [base64_decode('HgxBl1kJ4souh+ELRIHm/x8yTc/cgjDmiCNyJR/NJfs=', true), 'DYGEDF2ZBHRMULUH4EFUJAPG74PTETOP3SBDBZUIENZCKH6NEX5Q===='],
            'Partial zero edge case' => ['8', 'HA======'],
        ];
    }

    /**
     * @return array<string, array{0:string|false, 1:string}>
     */
    public static function base32decodeAsciiDataProvider(): array
    {
        return [
            ...self::base32encodeAsciiDataProvider(),
            'empty Characters' => ['', '         '],
        ];
    }

    /**
     * @return array<string, array{0: string|false, 1: string}>
     **/
    public static function base32encodeHexDataProvider(): array
    {
        return [
            'RFC Vector 1' => ['f', 'CO======'],
            'RFC Vector 2' => ['fo', 'CPNG===='],
            'RFC Vector 3' => ['foo', 'CPNMU==='],
            'RFC Vector 4' => ['foob', 'CPNMUOG='],
            'RFC Vector 5' => ['fooba', 'CPNMUOJ1'],
            'RFC Vector 6' => ['foobar', 'CPNMUOJ1E8======'],
            'Old Vector 1' => [' ', '40======'],
            'Old Vector 2' => ['  ', '40G0===='],
            'Old Vector 3' => ['   ', '40G20==='],
            'Old Vector 4' => ['    ', '40G2080='],
            'Old Vector 5' => ['     ', '40G20810'],
            'Old Vector 6' => ['      ', '40G2081040======'],
            'Empty String' => ['', ''],
            'Random Integers' => [base64_decode('HgxBl1kJ4souh+ELRIHm/x8yTc/cgjDmiCNyJR/NJfs=', true), '3O6435QP17HCKBK7S45K90F6VSFJ4JEFRI131PK84DP2A7UD4NTG===='],
        ];
    }

    /**
     * @return array<string, array{0: string|false, 1: string}>
     */
    public static function base32decodeHexDataProvider(): array
    {
        return [
            ...self::base32encodeHexDataProvider(),
            'empty Characters' => ['', '         '],
        ];
    }

    /**
     * Back and forth encoding must return the same result.
     *
     * @return array<string, array<string>>
     */
    public static function backAndForthDataProvider(): array
    {
        return [
            'Empty String' => [''],
            'Ten' => ['10'],
            'Test130' => ['test130'],
            'test' => ['test'],
            'Eight' => ['8'],
            'Zero' => ['0'],
            'Equals' => ['='],
            'Foobar' => ['foobar'],
        ];
    }

    /**
     * @return iterable<string, array{sequence: string, message: string, alphabet: string, padding:string}>
     */
    public static function invalidDecodingSequence(): iterable
    {
        yield 'characters outside of base32 extended hex alphabet' => [
            'sequence' => 'MZXQ====',
            'alphabet' => Base32::Hex,
        ];

        yield 'characters outside of base32 us ascii alphabet' => [
            'sequence' => '90890808',
            'alphabet' => Base32::Ascii,
        ];

        yield 'characters not upper-cased' => [
            'sequence' => 'MzxQ====',
            'alphabet' => Base32::Ascii,
        ];

        yield 'padding character in the middle of the sequence' => [
            'sequence' => 'A=ACA===',
            'alphabet' => Base32::Ascii,
        ];

        yield 'invalid padding length' => [
            'sequence' => 'A======',
            'alphabet' => Base32::Ascii,
        ];

        yield 'invalid encoded string length' => [
            'sequence' => 'A',
            'alphabet' => Base32::Ascii,
        ];
    }
}

<?php

declare(strict_types=1);

use Bakame\Stackwatch\Test\MetricsAssertions;
use Encoding\DecodingMode;
use Encoding\Base16;
use Encoding\UnableToDecodeException;
use PHPUnit\Framework\TestCase;

use function Encoding\base16_encode;
use function Encoding\base16_decode;

final class Base16Test extends TestCase
{
    public function testEncodeBasicString(): void
    {
        self::assertSame("48656C6C6F", base16_encode("Hello"));
    }

    public function testDecodeBasicString(): void
    {
        self::assertSame("Hello", base16_decode("48656C6C6F"));
    }

    public function testEncodeBasicStringWithLowerCase(): void
    {
        self::assertSame("48656c6c6f", base16_encode("Hello", Base16::Lower));
    }

    public function testDecodeBasicStringWithLowerCase(): void
    {
        self::assertSame("Hello", base16_decode("48656c6c6f", Base16::Lower));
    }

    public function testRoundTripConsistency(): void
    {
        $inputs = [
            "",
            "a",
            "abc",
            "The quick brown fox jumps over the lazy dog",
            "\0\1\2\xff",
        ];

        foreach ($inputs as $original) {
            $encoded = base16_encode($original);
            $decoded = base16_decode($encoded);

            self::assertSame($original, $decoded, "Failed round-trip for: " . bin2hex($original));
        }
    }

    public function testDecodeInvalidHexLength(): void
    {
        $this->expectException(UnableToDecodeException::class);

        base16_decode("abc");
    }

    public function testDecodeInvalidHexCharacters(): void
    {
        $this->expectException(UnableToDecodeException::class);

        base16_decode("gh"); // invalid chars
    }

    public function testEncodeEmptyString(): void
    {
        self::assertSame('', base16_encode(''));
    }

    public function testDecodeEmptyString(): void
    {
        self::assertSame('', base16_decode(''));
    }

    public function testUppercaseEncodingDecoding(): void
    {
        $original = "Hello, World!";
        $encoded = base16_encode($original);

        self::assertMatchesRegularExpression('/^[0-9A-F]+$/', $encoded);
        self::assertSame($original, base16_decode($encoded));
    }

    public function testLowercaseEncodingDecoding(): void
    {
        $original = "Hello, World!";
        $encoded = base16_encode($original, variant: Base16::Lower);

        self::assertMatchesRegularExpression('/^[0-9a-f]+$/', $encoded);
        self::assertSame($original, base16_decode($encoded, variant: Base16::Lower));
    }

    public function testForgivingDecoding(): void
    {
        $original = "Hello, World!";
        $encoded = "48656C6C6F2c20576F726C6421"; // uppercase

        self::assertSame($original, base16_decode(strtolower($encoded), decodingMode: DecodingMode::Forgiving));
        self::assertSame($original, base16_decode(strtoupper($encoded), variant:Base16::Lower, decodingMode: DecodingMode::Forgiving));
    }

    public function testDecodingWithIgnoredWhitespace(): void
    {
        $original = "Hello, World!";
        $encoded = "48 65\n6C\t6C\r6F 2C 20 57 6F 72 6C 64 21";

        self::assertSame($original, base16_decode($encoded, decodingMode: DecodingMode::Forgiving));
    }

    public function testInvalidCharactersThrowException(): void
    {
        $this->expectException(UnableToDecodeException::class);

        base16_decode("ZZ");
    }

    public function testOddLengthThrowsException(): void
    {
        $this->expectException(UnableToDecodeException::class);

        base16_decode("ABC");
    }

    public function testEmptyStringDecodesToEmpty(): void
    {
        self::assertSame("", base16_decode(""));
    }

    public function testAllBytesRoundTrip(): void
    {
        $bytes = '';
        for ($i = 0; $i < 256; $i++) {
            $bytes .= chr($i);
        }

        self::assertSame($bytes, base16_decode(base16_encode($bytes)));
    }

    public function test_constant_time_runs_full_loop_on_invalid_input(): void
    {
        $encodedValid = "48656C6C6F";
        $encodedInvalid = "48656Z6C6F";

        $metrics = stack_bench(
            callback: fn () => base16_decode($encodedValid),
            iterations: 20
        );
        $invalidMetrics = stack_bench(
            callback: function () use ($encodedInvalid) {
                try {
                    base16_decode($encodedInvalid);
                } catch (UnableToDecodeException) {
                }
            },
            iterations: 20);

        self::assertLessThan(
            2.0,
            max($metrics->executionTime, $invalidMetrics->executionTime) / min($metrics->executionTime, $invalidMetrics->executionTime),
        'Constant-time mode should take roughly the same time regardless of validity',
        );
    }
}
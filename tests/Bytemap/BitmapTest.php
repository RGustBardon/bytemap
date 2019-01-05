<?php

declare(strict_types=1);

/*
 * This file is part of the Bytemap package.
 *
 * (c) Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bytemap;

use PHPUnit\Framework\TestCase;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 * @covers \Bytemap\Bitmap
 */
final class BitmapTest extends TestCase
{
    use ArrayAccessTestTrait;
    use CloneableTestTrait;
    use CountableTestTrait;
    use IterableTestTrait;
    use JsonSerializableTestTrait;
    use MagicPropertiesTestTrait;
    use SerializableTestTrait;

    private const BINARY_DUMP_STATUS_DEFAULT = ' ';
    private const BINARY_DUMP_STATUS_MARKED = 'X';
    private const BINARY_DUMP_SEPARATOR_BYTE = '|';
    private const BINARY_DUMP_SEPARATOR_NIBBLE = ' ';
    private const BINARY_DUMP_TERMINATOR = '#';
    private const BINARY_DUMP_VALUE_FALSE = '0';
    private const BINARY_DUMP_VALUE_TRUE = '1';

    // `ArrayAccessTestTrait`
    public static function arrayAccessInstanceProvider(): \Generator
    {
        $elements = [false, true, false];
        $bytemap = new Bitmap();
        foreach ($elements as $element) {
            $bytemap[] = $element;
        }
        yield [$bytemap, false, $elements];
    }

    // `CloneableTestTrait`
    public static function cloneableInstanceProvider(): \Generator
    {
        yield from self::jsonSerializableInstanceProvider();
    }

    // `CountableTestTrait`
    public static function countableInstanceProvider(): \Generator
    {
        yield [new Bitmap(), [true, false]];
    }

    // `IterableTestTrait`
    public static function iterableInstanceProvider(): \Generator
    {
        yield [new Bitmap(), [false, true, false]];
    }

    // `JsonSerializableTestTrait`
    public static function jsonSerializableInstanceProvider(): \Generator
    {
        yield [new Bitmap(), false, [false, true, false]];
    }

    // `MagicPropertiesTestTrait`
    public static function magicPropertiesInstanceProvider(): \Generator
    {
        yield [new Bitmap()];
    }

    // `SerializableTestTrait`
    public static function invalidSerializedDataProvider(): \Generator
    {
        yield from [
            // C:14:"Bytemap\Bitmap":26:{a:2:{i:0;i:7;i:1;s:1:"a";}}
            ['C:14:"Bytemap\Bitmap":26:{a:3:{i:0;i:7;i:1;s:1:"a";}}', \UnexpectedValueException::class, 'error at offset'],
            ['C:14:"Bytemap\Bitmap":14:{a:1:{i:0;i:7;}}', \UnexpectedValueException::class, 'expected an array of two elements'],
            ['C:14:"Bytemap\Bitmap":30:{a:2:{i:0;s:1:"7";i:1;s:1:"a";}}', \TypeError::class, 'number of bits must be an integer'],
            ['C:14:"Bytemap\Bitmap":27:{a:2:{i:0;i:-7;i:1;s:1:"a";}}', \DomainException::class, 'number of bits must not be negative'],
            ['C:14:"Bytemap\Bitmap":38:{a:2:{i:0;i:7;i:1;a:1:{i:1;s:3:"bar";}}}', \TypeError::class, 'must be of type string'],
        ];
    }

    public static function serializableInstanceProvider(): \Generator
    {
        yield from self::jsonSerializableInstanceProvider();
    }

    // `BitmapTest`
    public function testMultibyteUnset(): void
    {
        $bitmap = new Bitmap();
        $bitmap[30] = true;
        $bitmap[32] = false;
        unset($bitmap[29]);
        self::assertFalse($bitmap[28]);
        self::assertTrue($bitmap[29]);
        self::assertFalse($bitmap[30]);
        self::assertFalse($bitmap[31]);
        self::assertFalse(isset($bitmap[32]));
    }

    public function testMultibyteIterator(): void
    {
        $bitmap = new Bitmap();
        $bitmap[30] = true;
        $bitmap[32] = false;
        $values = [];
        foreach ($bitmap as $key => $value) {
            $values[$key] = $value;
        }
        self::assertFalse($values[29]);
        self::assertTrue($values[30]);
        self::assertFalse($values[31]);
        self::assertFalse($values[32]);
        self::assertFalse(isset($values[33]));
    }

    public function testDeletionRandomly(): void
    {
        \mt_srand(0);
        for ($i = 0; $i < 10000; ++$i) {
            $elementCount = \mt_rand(0, 100);
            $firstIndex = \mt_rand(0, $elementCount);
            $howMany = \mt_rand(0, $elementCount - $firstIndex + 10);

            $bitmap = new Bitmap();
            $input = '';
            for ($index = 0; $index < $elementCount; ++$index) {
                $bit = 1 === \mt_rand(0, 1);
                $bitmap[$index] = $bit;
                $input .= $bit ? self::BINARY_DUMP_VALUE_TRUE : self::BINARY_DUMP_VALUE_FALSE;
            }

            $toBeDeleted = '';
            for ($index = 0; $index < $elementCount; ++$index) {
                $toBeDeleted .= ($index >= $firstIndex && $index < $firstIndex + $howMany) ? self::BINARY_DUMP_STATUS_MARKED : self::BINARY_DUMP_STATUS_DEFAULT;
            }

            $expected = \substr_replace($input, '', $firstIndex, $howMany);

            $bitmap->delete($firstIndex, $howMany);

            $actual = '';
            foreach ($bitmap as $value) {
                $actual .= $value ? self::BINARY_DUMP_VALUE_TRUE : self::BINARY_DUMP_VALUE_FALSE;
            }

            $format = <<<'NOWDOC'
First index:   %d
How many:      %d
Input:         %s
To be deleted: %s
Expected:      %s
Actual:        %s
NOWDOC;
            $message = \sprintf(
                $format,
                $firstIndex,
                $howMany,
                self::formatBinary($input),
                self::formatBinary($toBeDeleted),
                self::formatBinary($expected),
                self::formatBinary($actual)
            );

            self::assertSame($expected, $actual, $message);
        }
    }

    private static function formatBinary(string $binary): string
    {
        $formattedBinary = '';
        $lastIndex = \intdiv(\strlen($binary), 4);
        foreach (\str_split($binary, 4) as $index => $chunk) {
            $formattedBinary .= $chunk;
            if ($index < $lastIndex) {
                $formattedBinary .= (($index & 1) ? self::BINARY_DUMP_SEPARATOR_NIBBLE : self::BINARY_DUMP_SEPARATOR_BYTE);
            }
        }
        $formattedBinary .= self::BINARY_DUMP_TERMINATOR;

        return $formattedBinary;
    }
}

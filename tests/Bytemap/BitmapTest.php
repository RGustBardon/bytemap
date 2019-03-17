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

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 * @covers \Bytemap\Bitmap
 */
final class BitmapTest extends AbstractTestOfBytemap
{
    use ArrayAccessTestTrait;
    use CloneableTestTrait;
    use CountableTestTrait;
    use DeletionTestTrait;
    use FindingTestTrait;
    use InsertionTestTrait;
    use InvalidElementsGeneratorTrait;
    use InvalidTypeGeneratorsTrait;
    use IterableTestTrait;
    use JsonSerializableTestTrait;
    use JsonStreamTestTrait;
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

    // `JsonStreamTestTrait`
    public static function jsonStreamInstanceProvider(): \Generator
    {
        yield from self::jsonSerializableInstanceProvider();
    }

    // `SerializableTestTrait`
    public static function invalidSerializedDataProvider(): \Generator
    {
        yield from [
            // C:14:"Bytemap\Bitmap":26:{a:2:{i:0;i:7;i:1;s:1:"a";}}
            ['C:14:"Bytemap\Bitmap":26:{a:3:{i:0;i:7;i:1;s:1:"a";}}', \UnexpectedValueException::class, '~error at offset~i'],
            ['C:14:"Bytemap\Bitmap":14:{a:1:{i:0;i:7;}}', \UnexpectedValueException::class, '~expected an array of two elements~i'],
            ['C:14:"Bytemap\Bitmap":30:{a:2:{i:0;s:1:"7";i:1;s:1:"a";}}', \TypeError::class, '~number of bits must be an integer~i'],
            ['C:14:"Bytemap\Bitmap":27:{a:2:{i:0;i:-7;i:1;s:1:"a";}}', \DomainException::class, '~number of bits must not be negative~i'],
            ['C:14:"Bytemap\Bitmap":38:{a:2:{i:0;i:7;i:1;a:1:{i:1;s:3:"bar";}}}', \TypeError::class, '~must be of type string~i'],
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

    public function testUnavailableGrep(): void
    {
        $this->expectException(\LogicException::class);
        (new Bitmap())->grep(['~foo~']);
    }

    public static function randomizedBitmapProvider(): \Generator
    {
        \mt_srand(0);
        for ($i = 0; $i < 10000; ++$i) {
            $elementCount = \mt_rand(0, 100);
            $bitmap = new Bitmap();
            for ($index = 0; $index < $elementCount; ++$index) {
                $bitmap[$index] = 1 === \mt_rand(0, 1);
            }
            yield [$bitmap];
        }
    }

    /**
     * @dataProvider randomizedBitmapProvider
     */
    public function skippedTestInsertionRandomly(BytemapInterface $bitmap): void
    {
        $elementCount = \count($bitmap);
        $firstIndex = \mt_rand(0, $elementCount);
        $howMany = \mt_rand(0, $elementCount - $firstIndex + 10);

        $firstIndexStringified = \str_repeat(self::BINARY_DUMP_STATUS_DEFAULT, $elementCount);
        $firstIndexStringified[$firstIndex] = self::BINARY_DUMP_STATUS_MARKED;

        $input = self::stringify($bitmap);

        $toBeInserted = [];
        for ($index = 0; $index < $howMany; ++$index) {
            $toBeInserted[] = 1 === \mt_rand(0, 1);
        }
        $toBeInsertedStringified = self::stringify($toBeInserted);

        $format = <<<'NOWDOC'
First index:    %d
                %s
Input:          %s
To be inserted: %s

NOWDOC;
        $message = \sprintf(
            $format,
            $firstIndex,
            self::formatBinary($firstIndexStringified),
            self::formatBinary($input),
            self::formatBinary($toBeInsertedStringified)
        );
        $expected = \substr_replace($input, $toBeInsertedStringified, $firstIndex, 0);
        $bitmap->insert($toBeInserted, $firstIndex);
        self::assertBinary($expected, $bitmap, $message);
    }

    /**
     * @dataProvider randomizedBitmapProvider
     */
    public function skippedTestDeletionRandomly(BytemapInterface $bitmap): void
    {
        $elementCount = \count($bitmap);
        $firstIndex = \mt_rand(0, $elementCount);
        $howMany = \mt_rand(0, $elementCount - $firstIndex + 10);

        $input = self::stringify($bitmap);

        $toBeDeleted = '';
        for ($index = 0; $index < $elementCount; ++$index) {
            $toBeDeleted .= ($index >= $firstIndex && $index < $firstIndex + $howMany) ? self::BINARY_DUMP_STATUS_MARKED : self::BINARY_DUMP_STATUS_DEFAULT;
        }

        $format = <<<'NOWDOC'
First index:    %d
How many:       %d
Input:          %s
To be deleted:  %s

NOWDOC;
        $message = \sprintf(
            $format,
            $firstIndex,
            $howMany,
            self::formatBinary($input),
            self::formatBinary($toBeDeleted)
        );
        $expected = \substr_replace($input, '', $firstIndex, $howMany);
        $bitmap->delete($firstIndex, $howMany);
        self::assertBinary($expected, $bitmap, $message);
    }

    // `InsertionTestTrait`
    protected static function insertionInstanceProvider(): \Generator
    {
        yield from [
            [new Bitmap(), [false, true, false, true, false, true]],
            [new Bitmap(), [false, false, true, false, true, false]],
            [new Bitmap(), [false, false, false, true, true, true]],
            [new Bitmap(), [false, true, true, true, false, false]],
        ];
    }
    
    // `FindingTestTrait`
    protected static function seekableInstanceProvider(): \Generator
    {
        yield from self::insertionInstanceProvider();
    }

    // `DeletionTestTrait`
    protected static function deletionInstanceProvider(): \Generator
    {
        yield from self::insertionInstanceProvider();
    }

    private static function stringify(iterable $iterable): string
    {
        $result = '';
        foreach ($iterable as $value) {
            $result .= $value ? self::BINARY_DUMP_VALUE_TRUE : self::BINARY_DUMP_VALUE_FALSE;
        }

        return $result;
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

    private static function assertBinary(string $expected, BytemapInterface $bitmap, string $message): void
    {
        $actual = self::stringify($bitmap);
        $format = <<<'NOWDOC'
Expected:       %s
Actual:         %s
NOWDOC;
        $message .= \sprintf($format, self::formatBinary($expected), self::formatBinary($actual));
        self::assertSame($expected, $actual, $message);
    }
}

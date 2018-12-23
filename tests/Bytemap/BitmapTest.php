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
    use CountableTestTrait;
    use IterableTestTrait;
    use JsonSerializableTestTrait;
    use MagicPropertiesTestTrait;

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
}

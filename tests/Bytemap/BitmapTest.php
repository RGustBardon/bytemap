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
    use CountableTestTrait;
    use IterableTestTrait;
    use JsonSerializableTestTrait;
    use MagicPropertiesTestTrait;

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
}

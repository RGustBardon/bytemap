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
 * @covers \Bytemap\ArrayBytemap
 */
final class BytemapTest extends TestCase
{
    /**
     * @dataProvider implementationProvider
     */
    public function testArrayAccess(string $impl): void
    {
        $bytemap = new $impl();
        self::assertFalse(isset($bytemap[0]));
        self::assertFalse(isset($bytemap[2]));

        $bytemap[2] = 'x';
        self::assertFalse(isset($bytemap[0]));
        self::assertTrue(isset($bytemap[2]));
        self::assertSame('x', $bytemap[2]);

        $bytemap[2] = 'y';
        self::assertSame('y', $bytemap[2]);

        unset($bytemap[2]);
        self::assertFalse(isset($bytemap[2]));

        unset($bytemap[2]);
        self::assertFalse(isset($bytemap[2]));

        $bytemap = new $impl();
        $bytemap[] = 'a';
        $bytemap[] = 'b';

        self::assertTrue(isset($bytemap[0]));
        self::assertTrue(isset($bytemap[1]));
        self::assertFalse(isset($bytemap[2]));
        self::assertSame('a', $bytemap[0]);
        self::assertSame('b', $bytemap[1]);
    }

    public function implementationProvider(): array
    {
        return [[ArrayBytemap::class]];
    }
}

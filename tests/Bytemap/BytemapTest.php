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
 * @covers \Bytemap\Bytemap
 */
final class BytemapTest extends TestCase
{
    /**
     * @dataProvider arrayAccessProvider
     */
    public function testArrayAccess(string $impl, array $items): void
    {
        $bytemap = new $impl($items[0]);
        self::assertFalse(isset($bytemap[0]));
        self::assertFalse(isset($bytemap[2]));

        $bytemap[2] = $items[1];
        self::assertTrue(isset($bytemap[0]));
        self::assertTrue(isset($bytemap[2]));
        self::assertSame($items[0], $bytemap[0]);
        self::assertSame($items[0], $bytemap[1]);
        self::assertSame($items[1], $bytemap[2]);

        $bytemap[2] = $items[2];
        self::assertSame($items[2], $bytemap[2]);

        unset($bytemap[2]);
        self::assertFalse(isset($bytemap[2]));

        unset($bytemap[2]);
        self::assertFalse(isset($bytemap[2]));

        unset($bytemap[0]);
        self::assertTrue(isset($bytemap[0]));
        self::assertSame($items[0], $bytemap[0]);

        $bytemap = new $impl($items[0]);
        $bytemap[] = $items[1];
        $bytemap[] = $items[2];

        self::assertTrue(isset($bytemap[0]));
        self::assertTrue(isset($bytemap[1]));
        self::assertFalse(isset($bytemap[2]));
        self::assertSame($items[1], $bytemap[0]);
        self::assertSame($items[2], $bytemap[1]);
    }

    public function arrayAccessProvider(): \Generator
    {
        foreach ([ArrayBytemap::class, Bytemap::class] as $impl) {
            foreach ([['b', 'd', 'f'], ['bd', 'df', 'gg']] as $items) {
                yield [$impl, $items];
            }
        }
    }

    /**
     * @dataProvider arrayAccessProvider
     * @depends testArrayAccess
     */
    public function testCount(string $impl, array $items): void
    {
        $bytemap = new $impl($items[0]);
        self::assertCount(0, $bytemap);

        $bytemap[] = $items[1];
        self::assertCount(1, $bytemap);

        $bytemap[4] = $items[2];
        self::assertCount(5, $bytemap);

        $bytemap[4] = $items[1];
        self::assertCount(5, $bytemap);

        unset($bytemap[1]);
        self::assertCount(5, $bytemap);

        unset($bytemap[4]);
        self::assertCount(4, $bytemap);
    }
}

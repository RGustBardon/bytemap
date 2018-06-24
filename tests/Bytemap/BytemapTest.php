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
    public static function implementationProvider(): \Generator
    {
        yield from [[ArrayBytemap::class], [Bytemap::class]];
    }

    /**
     * @covers \Bytemap\AbstractBytemap
     * @dataProvider implementationProvider
     * @expectedException \ErrorException
     */
    public function testMagicGet(string $impl): void
    {
        (self::instantiate($impl, 'a'))->undefinedProperty;
    }

    /**
     * @covers \Bytemap\AbstractBytemap
     * @dataProvider implementationProvider
     * @expectedException \ErrorException
     */
    public function testMagicSet(string $impl): void
    {
        (self::instantiate($impl, 'a'))->undefinedProperty = 42;
    }

    /**
     * @covers \Bytemap\AbstractBytemap
     * @dataProvider implementationProvider
     * @expectedException \ErrorException
     */
    public function testMagicIsset(string $impl): void
    {
        isset((self::instantiate($impl, 'a'))->undefinedProperty);
    }

    /**
     * @covers \Bytemap\AbstractBytemap
     * @dataProvider implementationProvider
     * @expectedException \ErrorException
     */
    public function testMagicUnset(string $impl): void
    {
        unset((self::instantiate($impl, 'a'))->undefinedProperty);
    }

    public static function arrayAccessProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([['b', 'd', 'f'], ['bd', 'df', 'gg']] as $items) {
                yield [$impl, $items];
            }
        }
    }

    /**
     * @covers \Bytemap\ArrayBytemap::offsetExists
     * @covers \Bytemap\ArrayBytemap::offsetGet
     * @covers \Bytemap\ArrayBytemap::offsetSet
     * @covers \Bytemap\ArrayBytemap::offsetUnset
     * @covers \Bytemap\Bytemap::offsetExists
     * @covers \Bytemap\Bytemap::offsetGet
     * @covers \Bytemap\Bytemap::offsetSet
     * @covers \Bytemap\Bytemap::offsetUnset
     * @dataProvider arrayAccessProvider
     */
    public function testArrayAccess(string $impl, array $items): void
    {
        $bytemap = self::instantiate($impl, $items[0]);
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

        $bytemap = self::instantiate($impl, $items[0]);
        $bytemap[] = $items[1];
        $bytemap[] = $items[2];

        self::assertTrue(isset($bytemap[0]));
        self::assertTrue(isset($bytemap[1]));
        self::assertFalse(isset($bytemap[2]));
        self::assertSame($items[1], $bytemap[0]);
        self::assertSame($items[2], $bytemap[1]);
    }

    /**
     * @covers \Bytemap\ArrayBytemap::count
     * @covers \Bytemap\Bytemap::count
     * @dataProvider arrayAccessProvider
     * @depends testArrayAccess
     */
    public function testCount(string $impl, array $items): void
    {
        $bytemap = self::instantiate($impl, $items[0]);
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

    /**
     * @coversNothing
     * @dataProvider arrayAccessProvider
     * @depends testCount
     */
    public function testCloning(string $impl, array $items): void
    {
        $bytemap = self::instantiate($impl, $items[0]);
        $bytemap[] = $items[1];
        $bytemap[] = $items[2];
        $bytemap[] = $items[1];

        $clone = clone $bytemap;
        $count = \count($clone);
        self::assertSame(\count($bytemap), $count);
        for ($i = 0; $i < $count; ++$i) {
            self::assertSame($bytemap[$i], $clone[$i]);
        }
        $bytemap[10] = $items[1];
        $clone[10] = $items[2];
        self::assertSame($items[1], $bytemap[10]);
        self::assertSame($items[0], $clone[9]);
        self::assertSame($items[2], $clone[10]);
    }

    /**
     * @covers \Bytemap\ArrayBytemap::getIterator
     * @covers \Bytemap\Bytemap::getIterator
     * @dataProvider arrayAccessProvider
     * @depends testArrayAccess
     */
    public function testIteratorAggregate(string $impl, array $items): void
    {
        $sequence = [$items[1], $items[2], $items[1]];

        $bytemap = self::instantiate($impl, $items[0]);
        foreach ($bytemap as $key => $value) {
            self::fail();
        }

        self::pushItems($bytemap, ...$sequence);
        self::assertSequence($sequence, $bytemap);
    }

    /**
     * @covers \Bytemap\ArrayBytemap::jsonSerialize
     * @covers \Bytemap\Bytemap::jsonSerialize
     * @dataProvider arrayAccessProvider
     * @depends testArrayAccess
     */
    public function testJsonSerializable(string $impl, array $items): void
    {
        $sequence = [$items[1], $items[2], $items[1], $items[0], $items[0]];

        $bytemap = self::instantiate($impl, $items[0]);
        self::pushItems($bytemap, ...$sequence);

        self::assertSame(\json_encode([$items[0], $sequence]), \json_encode($bytemap));
    }

    /**
     * @covers \Bytemap\ArrayBytemap::serialize
     * @covers \Bytemap\ArrayBytemap::unserialize
     * @covers \Bytemap\Bytemap::serialize
     * @covers \Bytemap\Bytemap::unserialize
     * @dataProvider arrayAccessProvider
     * @depends testArrayAccess
     */
    public function testSerializable(string $impl, array $items): void
    {
        $sequence = [$items[1], $items[2], $items[1], $items[0], $items[0]];

        $bytemap = self::instantiate($impl, $items[0]);
        self::pushItems($bytemap, ...$sequence);

        $copy = \unserialize(\serialize($bytemap), ['allowed_classes' => [$impl]]);
        self::assertNotSame($bytemap, $copy);
        self::assertSequence($sequence, $copy);
    }

    private static function instantiate(string $impl, ...$args): BytemapInterface
    {
        return new $impl(...$args);
    }

    private static function pushItems(BytemapInterface $bytemap, ...$items): void
    {
        foreach ($items as $item) {
            $bytemap[] = $item;
        }
    }

    private static function assertSequence(array $sequence, BytemapInterface $bytemap): void
    {
        $i = 0;
        foreach ($bytemap as $key => $value) {
            self::assertSame($i, $key);
            self::assertSame($sequence[$key], $value);
            ++$i;
        }
        self::assertSame(\count($sequence), $i);
    }
}

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
 * @covers \Bytemap\AbstractBytemap
 * @covers \Bytemap\Benchmark\ArrayBytemap
 * @covers \Bytemap\Benchmark\DsBytemap
 * @covers \Bytemap\Benchmark\SplBytemap
 * @covers \Bytemap\Bytemap
 */
final class NativeFunctionalityTest extends AbstractTestOfBytemap
{
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

    /**
     * @covers \Bytemap\AbstractBytemap::offsetExists
     * @covers \Bytemap\AbstractBytemap::offsetGet
     * @covers \Bytemap\AbstractBytemap::offsetSet
     * @covers \Bytemap\AbstractBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetSet
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\DsBytemap::offsetSet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\SplBytemap::offsetSet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetUnset
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
     * @covers \Bytemap\AbstractBytemap::count
     * @covers \Bytemap\Bytemap::count
     * @dataProvider arrayAccessProvider
     * @depends testArrayAccess
     */
    public function testCountable(string $impl, array $items): void
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
     * @covers \Bytemap\Benchmark\DsBytemap::__clone
     * @covers \Bytemap\Benchmark\SplBytemap::__clone
     * @dataProvider arrayAccessProvider
     * @depends testCountable
     */
    public function testCloning(string $impl, array $items): void
    {
        $bytemap = self::instantiate($impl, $items[0]);
        $bytemap[] = $items[1];
        $bytemap[] = $items[2];
        $bytemap[] = $items[1];

        $clone = clone $bytemap;
        $size = \count($clone);
        self::assertSame(\count($bytemap), $size);
        for ($i = 0; $i < $size; ++$i) {
            self::assertSame($bytemap[$i], $clone[$i]);
        }

        $bytemap[] = $items[1];
        self::assertCount($size, $clone);
        self::assertCount($size + 1, $bytemap);
        unset($bytemap[$size + 1]);

        self::assertDefaultItem($items[0], $clone, $items[1]);
        self::assertDefaultItem($items[0], $bytemap, $items[2]);
    }

    /**
     * @covers \Bytemap\AbstractBytemap::getIterator
     * @covers \Bytemap\Benchmark\DsBytemap::getIterator
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

        $iterations = [];
        foreach ($bytemap as $outerKey => $outerItem) {
            if (1 === $outerKey) {
                $bytemap[] = $items[2];
            }
            $innerIteration = [];
            foreach ($bytemap as $innerKey => $innerItem) {
                if (1 === $innerKey) {
                    $bytemap[2] = $items[0];
                }
                $innerIteration[] = [$innerKey, $innerItem];
            }
            $iterations[] = $innerIteration;
            $iterations[] = [$outerKey, $outerItem];
        }
        self::assertSame([
            [[0, $items[1]], [1, $items[2]], [2, $items[1]]],
            [0, $items[1]],
            [[0, $items[1]], [1, $items[2]], [2, $items[0]], [3, $items[2]]],
            [1, $items[2]],
            [[0, $items[1]], [1, $items[2]], [2, $items[0]], [3, $items[2]]],
            [2, $items[1]],
        ], $iterations);
    }

    /**
     * @covers \Bytemap\AbstractBytemap::jsonSerialize
     * @covers \Bytemap\Benchmark\DsBytemap::jsonSerialize
     * @covers \Bytemap\Benchmark\SplBytemap::jsonSerialize
     * @covers \Bytemap\Bytemap::jsonSerialize
     * @dataProvider arrayAccessProvider
     * @depends testCountable
     */
    public function testJsonSerializable(string $impl, array $items): void
    {
        $bytemap = self::instantiate($impl, $items[0]);
        self::assertNativeJson([], $bytemap);

        $sequence = [$items[1], $items[2], $items[1]];
        self::pushItems($bytemap, ...$sequence);
        $bytemap[4] = $items[0];
        \array_push($sequence, $items[0], $items[0]);
        self::assertNativeJson($sequence, $bytemap);
    }

    /**
     * @covers \Bytemap\AbstractBytemap::serialize
     * @covers \Bytemap\AbstractBytemap::unserialize
     * @covers \Bytemap\Benchmark\SplBytemap::unserialize
     * @dataProvider arrayAccessProvider
     * @depends testCountable
     */
    public function testSerializable(string $impl, array $items): void
    {
        $sequence = [$items[1], $items[2], $items[1], $items[0], $items[0]];

        $bytemap = self::instantiate($impl, $items[0]);
        self::pushItems($bytemap, ...$sequence);

        $copy = \unserialize(\serialize($bytemap), ['allowed_classes' => [$impl]]);
        self::assertNotSame($bytemap, $copy);
        self::assertSequence($sequence, $copy);
        self::assertDefaultItem($items[0], $copy, $items[1]);
        self::assertDefaultItem($items[0], $bytemap, $items[2]);
    }

    private static function assertNativeJson($expected, $actual): void
    {
        $expectedJson = \json_encode($expected);
        self::assertSame(\JSON_ERROR_NONE, \json_last_error());

        $actualJson = \json_encode($actual);
        self::assertSame(\JSON_ERROR_NONE, \json_last_error());

        self::assertSame($expectedJson, $actualJson);
    }
}

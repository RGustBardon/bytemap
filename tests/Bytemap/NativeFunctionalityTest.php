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
     * @expectedException \LengthException
     */
    public function testConstructorEmptyString(string $impl): void
    {
        self::instantiate($impl, '');
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

    /**
     * @covers \Bytemap\AbstractBytemap::offsetExists
     * @dataProvider nullOffsetProvider
     * @dataProvider invalidOffsetTypeProvider
     *
     * @param mixed $offset
     */
    public function testExistsInvalidType(string $impl, array $items, $offset): void
    {
        self::assertFalse(isset(self::instantiate($impl, $items[0])[$offset]));
    }

    /**
     * @covers \Bytemap\AbstractBytemap::offsetExists
     * @dataProvider negativeOffsetProvider
     * @dataProvider outOfRangeOffsetProvider
     */
    public function testExistsOutOfRange(string $impl, array $items, int $itemCount, int $offset): void
    {
        self::assertFalse(isset(self::instantiateWithSize($impl, $items, $itemCount)[$offset]));
    }

    /**
     * @covers \Bytemap\AbstractBytemap::offsetGet
     * @covers \Bytemap\Bytemap::offsetGet
     * @dataProvider nullOffsetProvider
     * @dataProvider invalidOffsetTypeProvider
     * @expectedException \TypeError
     *
     * @param mixed $offset
     */
    public function testGetInvalidType(string $impl, array $items, $offset): void
    {
        self::instantiate($impl, $items[0])[$offset];
    }

    /**
     * @covers \Bytemap\AbstractBytemap::offsetGet
     * @covers \Bytemap\Bytemap::offsetGet
     * @dataProvider negativeOffsetProvider
     * @dataProvider outOfRangeOffsetProvider
     * @expectedException \OutOfRangeException
     */
    public function testGetOutOfRange(string $impl, array $items, int $itemCount, int $offset): void
    {
        self::instantiateWithSize($impl, $items, $itemCount)[$offset];
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetSet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetSet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetSet
     * @covers \Bytemap\Bytemap::offsetSet
     * @dataProvider invalidOffsetTypeProvider
     * @expectedException \TypeError
     *
     * @param mixed $offset
     */
    public function testSetInvalidOffsetType(string $impl, array $items, $offset): void
    {
        $bytemap = self::instantiate($impl, $items[0]);
        $bytemap[$offset] = $items[0];
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetSet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetSet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetSet
     * @covers \Bytemap\Bytemap::offsetSet
     * @dataProvider negativeOffsetProvider
     * @expectedException \OutOfRangeException
     */
    public function testSetNegativeOffset(string $impl, array $items, int $itemCount, int $offset): void
    {
        $bytemap = self::instantiateWithSize($impl, $items, $itemCount);
        $bytemap[$offset] = $items[0];
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetSet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetSet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetSet
     * @covers \Bytemap\Bytemap::offsetSet
     * @dataProvider invalidItemTypeProvider
     * @expectedException \TypeError
     *
     * @param mixed $invalidItem
     */
    public function testSetInvalidItemType(string $impl, array $items, $invalidItem): void
    {
        $bytemap = self::instantiate($impl, $items[0]);
        $bytemap[] = $invalidItem;
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetSet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetSet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetSet
     * @covers \Bytemap\Bytemap::offsetSet
     * @dataProvider invalidLengthProvider
     * @expectedException \LengthException
     */
    public function testSetInvalidLength(string $impl, string $defaultItem, string $invalidItem): void
    {
        $bytemap = self::instantiate($impl, $defaultItem);
        $bytemap[] = $invalidItem;
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\DsBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\SplBytemap::offsetUnset
     * @covers \Bytemap\Bytemap::offsetUnset
     * @dataProvider nullOffsetProvider
     * @dataProvider invalidOffsetTypeProvider
     * @doesNotPerformAssertions
     *
     * @param mixed $offset
     */
    public function testUnsetInvalidType(string $impl, array $items, $offset): void
    {
        $bytemap = self::instantiate($impl, $items[0]);
        unset($bytemap[$offset]);
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\DsBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\SplBytemap::offsetUnset
     * @covers \Bytemap\Bytemap::offsetUnset
     * @dataProvider negativeOffsetProvider
     * @dataProvider outOfRangeOffsetProvider
     * @doesNotPerformAssertions
     */
    public function testUnsetOutOfRange(string $impl, array $items, int $itemCount, int $offset): void
    {
        $bytemap = self::instantiateWithSize($impl, $items, $itemCount);
        unset($bytemap[$offset]);
    }

    /**
     * @covers \Bytemap\AbstractBytemap::offsetExists
     * @covers \Bytemap\AbstractBytemap::offsetGet
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
     * @depends testExistsInvalidType
     * @depends testExistsOutOfRange
     * @depends testGetInvalidType
     * @depends testGetOutOfRange
     * @depends testSetInvalidOffsetType
     * @depends testSetNegativeOffset
     * @depends testSetInvalidItemType
     * @depends testSetInvalidLength
     * @depends testUnsetInvalidType
     * @depends testUnsetOutOfRange
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

    public static function invalidSerializedDataProvider(): \Generator
    {
        yield from [
            // C:30:"Bytemap\\Benchmark\\ArrayBytemap":44:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;s:3:"bar";}}}
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":10:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;s:3:"bar";}}}', \UnexpectedValueException::class, 'error at offset'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, 'an array of two elements'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":52:{a:3:{i:0;s:3:"foo";i:1;a:1:{i:1;s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":40:{a:2:{i:0;i:100;i:1;a:1:{i:1;s:3:"bar";}}}', \TypeError::class, 'must be of type string'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":41:{a:2:{i:0;s:0:"";i:1;a:1:{i:1;s:3:"bar";}}}', \LengthException::class, 'cannot be an empty string'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, 'must be of type array'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":50:{a:2:{i:0;s:3:"foo";i:1;a:1:{s:3:"baz";s:3:"bar";}}}', \TypeError::class, 'index must be of type integer'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":45:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:-1;s:3:"bar";}}}', \OutOfRangeException::class, 'negative index'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":39:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":43:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;s:2:"ba";}}}', \LengthException::class, 'value must be exactly'],

            // DsBytemap
            ['C:27:"Bytemap\\Benchmark\\DsBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, 'an array of two elements'],

            ['C:27:"Bytemap\\Benchmark\\DsBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, 'must be a Ds\\Vector'],
            ['C:27:"Bytemap\\Benchmark\\DsBytemap":68:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:2:"ba";}}}', \TypeError::class, 'must be a Ds\\Vector'],

            // C:28:"Bytemap\\Benchmark\\SplBytemap":69:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":10:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}', \UnexpectedValueException::class, 'error at offset'],

            ['C:28:"Bytemap\\Benchmark\\SplBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, 'an array of two elements'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":77:{a:3:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

            ['C:28:"Bytemap\\Benchmark\\SplBytemap":65:{a:2:{i:0;i:100;i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}', \TypeError::class, 'must be of type string'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":66:{a:2:{i:0;s:0:"";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}', \LengthException::class, 'cannot be an empty string'],

            ['C:28:"Bytemap\\Benchmark\\SplBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, 'must be an SplFixedArray'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":65:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}}}', \TypeError::class, 'must be an SplFixedArray'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":64:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":68:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:2:"ba";}}}', \LengthException::class, 'value must be exactly'],

            // C:15:"Bytemap\\Bytemap":37:{a:2:{i:0;s:3:"foo";i:1;s:6:"foobar";}}
            ['C:15:"Bytemap\\Bytemap":10:{a:2:{i:0;s:3:"foo";i:1;s:6:"foobar";}}', \UnexpectedValueException::class, 'error at offset'],

            ['C:15:"Bytemap\\Bytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, 'an array of two elements'],
            ['C:15:"Bytemap\\Bytemap":45:{a:3:{i:0;s:3:"foo";i:1;s:6:"foobar";i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

            ['C:15:"Bytemap\\Bytemap":33:{a:2:{i:0;i:100;i:1;s:6:"foobar";}}', \TypeError::class, 'must be of type string'],
            ['C:15:"Bytemap\\Bytemap":34:{a:2:{i:0;s:0:"";i:1;s:6:"foobar";}}', \LengthException::class, 'cannot be an empty string'],

            ['C:15:"Bytemap\\Bytemap":44:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;s:3:"bar";}}}', \TypeError::class, 'must be of type string'],
            ['C:15:"Bytemap\\Bytemap":36:{a:2:{i:0;s:3:"foo";i:1;s:5:"fooba";}}', \LengthException::class, 'not a multiple'],
        ];

        if (\extension_loaded('ds')) {
            yield from [
                // C:27:"Bytemap\\Benchmark\\DsBytemap":65:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}}}
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":10:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}}}', \UnexpectedValueException::class, 'error at offset'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":73:{a:3:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":60:{a:2:{i:0;i:100;i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":61:{a:2:{i:0;s:0:"";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \LengthException::class, 'cannot be an empty string'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":64:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":64:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";s:2:"ba";}}}', \LengthException::class, 'value must be exactly'],
            ];
        } else {
            yield from [
                // C:27:"Bytemap\\Benchmark\\DsBytemap":133:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"Ds\\Vectorarray";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"Ds\\Vectorcapacity";i:8;}}}
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":10:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x00".'Ds\\Vector'."\x00".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x00".'Ds\\Vector'."\x00".'capacity";i:8;}}}', \UnexpectedValueException::class, 'error at offset'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":141:{a:3:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x00".'Ds\\Vector'."\x00".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x00".'Ds\\Vector'."\x00".'capacity";i:8;}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":129:{a:2:{i:0;i:100;i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x00".'Ds\\Vector'."\x00".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x00".'Ds\\Vector'."\x00".'capacity";i:8;}}}', \TypeError::class, 'must be of type string'],
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":130:{a:2:{i:0;s:0:"";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x00".'Ds\\Vector'."\x00".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x00".'Ds\\Vector'."\x00".'capacity";i:8;}}}', \LengthException::class, 'cannot be an empty string'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":128:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x00".'Ds\\Vector'."\x00".'array";a:2:{i:0;s:3:"foo";i:1;i:42;}s:19:"'."\x00".'Ds\\Vector'."\x00".'capacity";i:8;}}}', \TypeError::class, 'must be of type string'],
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":132:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x00".'Ds\\Vector'."\x00".'array";a:2:{i:0;s:3:"foo";i:1;s:2:"ba";}s:19:"'."\x00".'Ds\\Vector'."\x00".'capacity";i:8;}}}', \LengthException::class, 'value must be exactly'],
            ];
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::unserialize
     * @covers \Bytemap\Benchmark\SplBytemap::unserialize
     * @dataProvider invalidSerializedDataProvider
     */
    public function testUnserializeInvalidData(string $data, string $expectedThrowable, string $expectedMessage): void
    {
        try {
            \unserialize($data);
        } catch (\Throwable $e) {
            if (!($e instanceof $expectedThrowable)) {
                $format = 'Failed asserting that a throwable of type %s is thrown as opposed to %s with message "%s"';
                self::fail(\sprintf($format, $expectedThrowable, \get_class($e), $e->getMessage()));
            }
        }
        self::assertTrue(isset($e), 'Nothing thrown although "\\'.$expectedThrowable.'" was expected.');
        self::assertContains($expectedMessage, $e->getMessage(), '', true);
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

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
     * @expectedException \DomainException
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

    public static function nullIndexProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $elements]) {
            yield [$impl, $elements, null];
        }
    }

    public static function invalidIndexTypeProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $elements]) {
            foreach ([
                false, true,
                0., 1.,
                '', '+0', '00', '01', '0e0', '0a', 'a0', '01', '1e0', '1a', 'a1',
                [], [0], [1],
                new \stdClass(), new class() {
                    public function __toString(): string
                    {
                        return '0';
                    }
                },
                \fopen('php://memory', 'rb'),
                function (): int { return 0; },
                function (): \Generator { yield 0; },
            ] as $index) {
                yield [$impl, $elements, $index];
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::offsetExists
     * @dataProvider nullIndexProvider
     * @dataProvider invalidIndexTypeProvider
     *
     * @param mixed $index
     */
    public function testExistsInvalidType(string $impl, array $elements, $index): void
    {
        self::assertFalse(isset(self::instantiate($impl, $elements[0])[$index]));
    }

    public static function negativeIndexProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $elements]) {
            yield [$impl, $elements, 0, -1];
        }
    }

    public static function outOfRangeIndexProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $elements]) {
            foreach ([
                [0, 0],
                [1, 1],
            ] as [$elementCount, $index]) {
                yield [$impl, $elements, $elementCount, $index];
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::offsetExists
     * @dataProvider negativeIndexProvider
     * @dataProvider outOfRangeIndexProvider
     */
    public function testExistsOutOfRange(string $impl, array $elements, int $elementCount, int $index): void
    {
        self::assertFalse(isset(self::instantiateWithSize($impl, $elements, $elementCount)[$index]));
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetGet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetGet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetGet
     * @covers \Bytemap\Bytemap::offsetGet
     * @dataProvider nullIndexProvider
     * @dataProvider invalidIndexTypeProvider
     * @expectedException \TypeError
     *
     * @param mixed $index
     */
    public function testGetInvalidType(string $impl, array $elements, $index): void
    {
        self::instantiate($impl, $elements[0])[$index];
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetGet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetGet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetGet
     * @covers \Bytemap\Bytemap::offsetGet
     * @dataProvider negativeIndexProvider
     * @dataProvider outOfRangeIndexProvider
     * @expectedException \OutOfRangeException
     */
    public function testGetOutOfRange(string $impl, array $elements, int $elementCount, int $index): void
    {
        self::instantiateWithSize($impl, $elements, $elementCount)[$index];
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetSet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetSet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetSet
     * @covers \Bytemap\Bytemap::offsetSet
     * @dataProvider invalidIndexTypeProvider
     * @expectedException \TypeError
     *
     * @param mixed $index
     */
    public function testSetInvalidIndexType(string $impl, array $elements, $index): void
    {
        $bytemap = self::instantiate($impl, $elements[0]);
        $bytemap[$index] = $elements[0];
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetSet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetSet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetSet
     * @covers \Bytemap\Bytemap::offsetSet
     * @dataProvider negativeIndexProvider
     * @expectedException \OutOfRangeException
     */
    public function testSetNegativeIndex(string $impl, array $elements, int $elementCount, int $index): void
    {
        $bytemap = self::instantiateWithSize($impl, $elements, $elementCount);
        $bytemap[$index] = $elements[0];
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetSet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetSet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetSet
     * @covers \Bytemap\Bytemap::offsetSet
     * @dataProvider invalidElementTypeProvider
     * @expectedException \TypeError
     *
     * @param mixed $invalidElement
     */
    public function testSetInvalidElementType(string $impl, array $elements, $invalidElement): void
    {
        $bytemap = self::instantiate($impl, $elements[0]);
        $bytemap[] = $invalidElement;
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetSet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetSet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetSet
     * @covers \Bytemap\Bytemap::offsetSet
     * @dataProvider invalidLengthProvider
     * @expectedException \DomainException
     */
    public function testSetInvalidLength(string $impl, string $defaultValue, string $invalidElement): void
    {
        $bytemap = self::instantiate($impl, $defaultValue);
        $bytemap[] = $invalidElement;
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\DsBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\SplBytemap::offsetUnset
     * @covers \Bytemap\Bytemap::offsetUnset
     * @dataProvider nullIndexProvider
     * @dataProvider invalidIndexTypeProvider
     * @doesNotPerformAssertions
     *
     * @param mixed $index
     */
    public function testUnsetInvalidType(string $impl, array $elements, $index): void
    {
        $bytemap = self::instantiate($impl, $elements[0]);
        unset($bytemap[$index]);
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\DsBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\SplBytemap::offsetUnset
     * @covers \Bytemap\Bytemap::offsetUnset
     * @dataProvider negativeIndexProvider
     * @dataProvider outOfRangeIndexProvider
     * @doesNotPerformAssertions
     */
    public function testUnsetOutOfRange(string $impl, array $elements, int $elementCount, int $index): void
    {
        $bytemap = self::instantiateWithSize($impl, $elements, $elementCount);
        unset($bytemap[$index]);
    }

    /**
     * @covers \Bytemap\AbstractBytemap::offsetExists
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetGet
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetSet
     * @covers \Bytemap\Benchmark\ArrayBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\DsBytemap::offsetGet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetSet
     * @covers \Bytemap\Benchmark\DsBytemap::offsetUnset
     * @covers \Bytemap\Benchmark\SplBytemap::offsetGet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetSet
     * @covers \Bytemap\Benchmark\SplBytemap::offsetUnset
     * @covers \Bytemap\Bytemap::offsetGet
     * @covers \Bytemap\Bytemap::offsetSet
     * @covers \Bytemap\Bytemap::offsetUnset
     * @dataProvider arrayAccessProvider
     */
    public function testArrayAccess(string $impl, array $elements): void
    {
        $bytemap = self::instantiate($impl, $elements[0]);
        self::assertFalse(isset($bytemap[0]));
        self::assertFalse(isset($bytemap[2]));

        $bytemap[2] = $elements[1];
        self::assertTrue(isset($bytemap[0]));
        self::assertTrue(isset($bytemap[2]));
        self::assertSame($elements[0], $bytemap[0]);
        self::assertSame($elements[0], $bytemap[1]);
        self::assertSame($elements[1], $bytemap[2]);

        $bytemap[2] = $elements[2];
        self::assertSame($elements[2], $bytemap[2]);

        unset($bytemap[2]);
        self::assertFalse(isset($bytemap[2]));

        unset($bytemap[0]);
        self::assertTrue(isset($bytemap[0]));
        self::assertSame($elements[0], $bytemap[0]);

        $bytemap = self::instantiate($impl, $elements[0]);
        $bytemap[] = $elements[1];
        $bytemap[] = $elements[2];

        self::assertTrue(isset($bytemap[0]));
        self::assertTrue(isset($bytemap[1]));
        self::assertFalse(isset($bytemap[2]));
        self::assertSame($elements[1], $bytemap[0]);
        self::assertSame($elements[2], $bytemap[1]);

        unset($bytemap[0]);
        self::assertSame($elements[2], $bytemap[0]);
        self::assertFalse(isset($bytemap[1]));
    }

    /**
     * @covers \Bytemap\AbstractBytemap::count
     * @covers \Bytemap\Bytemap::count
     * @dataProvider arrayAccessProvider
     * @depends testArrayAccess
     */
    public function testCountable(string $impl, array $elements): void
    {
        $bytemap = self::instantiate($impl, $elements[0]);
        self::assertCount(0, $bytemap);

        $bytemap[] = $elements[1];
        self::assertCount(1, $bytemap);

        $bytemap[4] = $elements[2];
        self::assertCount(5, $bytemap);

        $bytemap[4] = $elements[1];
        self::assertCount(5, $bytemap);

        unset($bytemap[1]);
        self::assertCount(4, $bytemap);

        unset($bytemap[4]);
        self::assertCount(4, $bytemap);

        unset($bytemap[3]);
        self::assertCount(3, $bytemap);
    }

    /**
     * @covers \Bytemap\Benchmark\DsBytemap::__clone
     * @covers \Bytemap\Benchmark\SplBytemap::__clone
     * @dataProvider arrayAccessProvider
     * @depends testCountable
     */
    public function testCloning(string $impl, array $elements): void
    {
        $bytemap = self::instantiate($impl, $elements[0]);
        $bytemap[] = $elements[1];
        $bytemap[] = $elements[2];
        $bytemap[] = $elements[1];

        $clone = clone $bytemap;
        $size = \count($clone);
        self::assertSame(\count($bytemap), $size);
        for ($i = 0; $i < $size; ++$i) {
            self::assertSame($bytemap[$i], $clone[$i]);
        }

        $bytemap[] = $elements[1];
        self::assertCount($size, $clone);
        self::assertCount($size + 1, $bytemap);
        unset($bytemap[$size + 1]);

        self::assertDefaultValue($elements[0], $clone, $elements[1]);
        self::assertDefaultValue($elements[0], $bytemap, $elements[2]);
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::getIterator
     * @covers \Bytemap\Benchmark\DsBytemap::getIterator
     * @covers \Bytemap\Benchmark\SplBytemap::getIterator
     * @covers \Bytemap\Bytemap::getIterator
     * @dataProvider arrayAccessProvider
     * @depends testArrayAccess
     */
    public function testIteratorAggregate(string $impl, array $elements): void
    {
        $sequence = [$elements[1], $elements[2], $elements[1]];

        $bytemap = self::instantiate($impl, $elements[0]);
        foreach ($bytemap as $element) {
            self::fail();
        }

        self::pushElements($bytemap, ...$sequence);
        self::assertSequence($sequence, $bytemap);

        $iterations = [];
        foreach ($bytemap as $outerIndex => $outerElement) {
            if (1 === $outerIndex) {
                $bytemap[] = $elements[2];
            }
            $innerIteration = [];
            foreach ($bytemap as $innerIndex => $innerElement) {
                if (1 === $innerIndex) {
                    $bytemap[2] = $elements[0];
                }
                $innerIteration[] = [$innerIndex, $innerElement];
            }
            $iterations[] = $innerIteration;
            $iterations[] = [$outerIndex, $outerElement];
        }
        self::assertSame([
            [[0, $elements[1]], [1, $elements[2]], [2, $elements[1]]],
            [0, $elements[1]],
            [[0, $elements[1]], [1, $elements[2]], [2, $elements[0]], [3, $elements[2]]],
            [1, $elements[2]],
            [[0, $elements[1]], [1, $elements[2]], [2, $elements[0]], [3, $elements[2]]],
            [2, $elements[1]],
        ], $iterations);
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::jsonSerialize
     * @covers \Bytemap\Benchmark\DsBytemap::jsonSerialize
     * @covers \Bytemap\Benchmark\SplBytemap::jsonSerialize
     * @covers \Bytemap\Bytemap::jsonSerialize
     * @dataProvider arrayAccessProvider
     * @depends testCountable
     */
    public function testJsonSerializable(string $impl, array $elements): void
    {
        $bytemap = self::instantiate($impl, $elements[0]);
        self::assertNativeJson([], $bytemap);

        $sequence = [$elements[1], $elements[2], $elements[1]];
        self::pushElements($bytemap, ...$sequence);
        $bytemap[4] = $elements[0];
        \array_push($sequence, $elements[0], $elements[0]);
        self::assertNativeJson($sequence, $bytemap);
    }

    public static function invalidSerializedDataProvider(): \Generator
    {
        yield from [
            // C:30:"Bytemap\\Benchmark\\ArrayBytemap":44:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;s:3:"bar";}}}
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":44:{a:2:{i:0;s:2:"foo";i:1;a:1:{i:1;s:3:"bar";}}}', \UnexpectedValueException::class, 'error at offset'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, 'an array of two elements'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":52:{a:3:{i:0;s:3:"foo";i:1;a:1:{i:1;s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":40:{a:2:{i:0;i:100;i:1;a:1:{i:1;s:3:"bar";}}}', \TypeError::class, 'must be of type string'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":41:{a:2:{i:0;s:0:"";i:1;a:1:{i:1;s:3:"bar";}}}', \DomainException::class, 'cannot be an empty string'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, 'must be of type array'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":50:{a:2:{i:0;s:3:"foo";i:1;a:1:{s:3:"baz";s:3:"bar";}}}', \TypeError::class, 'index must be of type int'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":45:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:-1;s:3:"bar";}}}', \OutOfRangeException::class, 'negative index'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":39:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":43:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;s:2:"ba";}}}', \DomainException::class, 'value must be exactly'],

            // DsBytemap
            ['C:27:"Bytemap\\Benchmark\\DsBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, 'an array of two elements'],

            ['C:27:"Bytemap\\Benchmark\\DsBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, 'must be a Ds\\Vector'],
            ['C:27:"Bytemap\\Benchmark\\DsBytemap":68:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:2:"ba";}}}', \TypeError::class, 'must be a Ds\\Vector'],

            // C:28:"Bytemap\\Benchmark\\SplBytemap":69:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":69:{a:2:{i:0;s:2:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}', \UnexpectedValueException::class, 'error at offset'],

            ['C:28:"Bytemap\\Benchmark\\SplBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, 'an array of two elements'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":77:{a:3:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

            ['C:28:"Bytemap\\Benchmark\\SplBytemap":65:{a:2:{i:0;i:100;i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}', \TypeError::class, 'must be of type string'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":66:{a:2:{i:0;s:0:"";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}', \DomainException::class, 'cannot be an empty string'],

            ['C:28:"Bytemap\\Benchmark\\SplBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, 'must be an SplFixedArray'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":65:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}}}', \TypeError::class, 'must be an SplFixedArray'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":64:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":68:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:2:"ba";}}}', \DomainException::class, 'value must be exactly'],

            // C:15:"Bytemap\\Bytemap":37:{a:2:{i:0;s:3:"foo";i:1;s:6:"foobar";}}
            ['C:15:"Bytemap\\Bytemap":37:{a:2:{i:0;s:2:"foo";i:1;s:6:"foobar";}}', \UnexpectedValueException::class, 'error at offset'],

            ['C:15:"Bytemap\\Bytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, 'an array of two elements'],
            ['C:15:"Bytemap\\Bytemap":45:{a:3:{i:0;s:3:"foo";i:1;s:6:"foobar";i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

            ['C:15:"Bytemap\\Bytemap":33:{a:2:{i:0;i:100;i:1;s:6:"foobar";}}', \TypeError::class, 'must be of type string'],
            ['C:15:"Bytemap\\Bytemap":34:{a:2:{i:0;s:0:"";i:1;s:6:"foobar";}}', \DomainException::class, 'cannot be an empty string'],

            ['C:15:"Bytemap\\Bytemap":44:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;s:3:"bar";}}}', \TypeError::class, 'must be of type string'],
            ['C:15:"Bytemap\\Bytemap":36:{a:2:{i:0;s:3:"foo";i:1;s:5:"fooba";}}', \DomainException::class, 'not a multiple'],
        ];

        if (\extension_loaded('ds')) {
            yield from [
                // C:27:"Bytemap\\Benchmark\\DsBytemap":65:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}}}
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":65:{a:2:{i:0;s:2:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}}}', \UnexpectedValueException::class, 'error at offset'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":73:{a:3:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":60:{a:2:{i:0;i:100;i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":61:{a:2:{i:0;s:0:"";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \DomainException::class, 'cannot be an empty string'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":64:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":64:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";s:2:"ba";}}}', \DomainException::class, 'value must be exactly'],
            ];
        } else {
            yield from [
                // C:27:"Bytemap\\Benchmark\\DsBytemap":133:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"Ds\\Vectorarray";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"Ds\\Vectorcapacity";i:8;}}}
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":133:{a:2:{i:0;s:2:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \UnexpectedValueException::class, 'error at offset'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":141:{a:3:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":129:{a:2:{i:0;i:100;i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \TypeError::class, 'must be of type string'],
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":130:{a:2:{i:0;s:0:"";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \DomainException::class, 'cannot be an empty string'],

                ['C:27:"Bytemap\\Benchmark\\DsBytemap":128:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;i:42;}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \TypeError::class, 'must be of type string'],
                ['C:27:"Bytemap\\Benchmark\\DsBytemap":132:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:2:"ba";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \DomainException::class, 'value must be exactly'],
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

    /**
     * @covers \Bytemap\AbstractBytemap::serialize
     * @covers \Bytemap\AbstractBytemap::unserialize
     * @covers \Bytemap\Benchmark\SplBytemap::unserialize
     * @dataProvider arrayAccessProvider
     * @depends testCountable
     */
    public function testSerializable(string $impl, array $elements): void
    {
        $sequence = [$elements[1], $elements[2], $elements[1], $elements[0], $elements[0]];

        $bytemap = self::instantiate($impl, $elements[0]);
        self::pushElements($bytemap, ...$sequence);

        $copy = \unserialize(\serialize($bytemap), ['allowed_classes' => [$impl]]);
        self::assertNotSame($bytemap, $copy);
        self::assertSequence($sequence, $copy);
        self::assertDefaultValue($elements[0], $copy, $elements[1]);
        self::assertDefaultValue($elements[0], $bytemap, $elements[2]);
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

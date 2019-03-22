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

use Bytemap\Benchmark\ArrayBytemap;
use Bytemap\Benchmark\DsDequeBytemap;
use Bytemap\Benchmark\DsVectorBytemap;
use Bytemap\Benchmark\SplBytemap;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 * @covers \Bytemap\AbstractBytemap
 * @covers \Bytemap\Benchmark\AbstractDsBytemap
 * @covers \Bytemap\Benchmark\ArrayBytemap
 * @covers \Bytemap\Benchmark\DsDequeBytemap
 * @covers \Bytemap\Benchmark\DsVectorBytemap
 * @covers \Bytemap\Benchmark\SplBytemap
 * @covers \Bytemap\Bytemap
 */
final class BytemapTest extends AbstractTestOfBytemap
{
    use InvalidLengthGeneratorTrait;
    use InvalidLengthTestTrait;

    // `ArrayAccessTestTrait`
    public static function arrayAccessInstanceProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['b', 'd', 'f'],
                ['bd', 'df', 'gg'],
            ] as $elements) {
                $defaultValue = \str_repeat("\x0", \strlen($elements[0]));
                $bytemap = new $impl($defaultValue);
                foreach ($elements as $element) {
                    $bytemap[] = $element;
                }
                yield [$bytemap, $defaultValue, $elements];
            }
        }
    }

    // `CloneableTestTrait`
    public static function cloneableInstanceProvider(): \Generator
    {
        yield from self::jsonSerializableInstanceProvider();
    }

    // `CountableTestTrait`
    public static function countableInstanceProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            yield [new $impl("\x00\x00"), ['ab', 'cd']];
        }
    }

    // `IterableTestTrait`
    public static function iterableInstanceProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['b', 'd', 'f'],
                ['bd', 'df', 'gg'],
            ] as $elements) {
                yield [new $impl(\str_repeat("\x0", \strlen($elements[0]))), $elements];
            }
        }
    }

    // `JsonSerializableTestTrait`
    public static function jsonSerializableInstanceProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['b', 'd', 'f'],
                ['bd', 'df', 'gg'],
            ] as $elements) {
                $defaultValue = \str_repeat("\x0", \strlen($elements[0]));
                $bytemap = new $impl($defaultValue);
                yield [$bytemap, $defaultValue, $elements];
            }
        }
    }

    // `JsonStreamTestTrait`
    public static function jsonStreamInstanceProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['b', 'd', 'f'],
                ['bd', 'df', 'gg'],
            ] as $elements) {
                $defaultValue = \str_repeat("\x0", \strlen($elements[0]));
                $invalidValue = $defaultValue."\x0";
                yield [new $impl($defaultValue), $defaultValue, $invalidValue, $elements];
            }
        }
    }

    // `SerializableTestTrait`
    public static function invalidSerializedDataProvider(): \Generator
    {
        yield from [
            // C:30:"Bytemap\\Benchmark\\ArrayBytemap":44:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;s:3:"bar";}}}
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":44:{a:2:{i:0;s:2:"foo";i:1;a:1:{i:1;s:3:"bar";}}}', \UnexpectedValueException::class, '~error at offset~i'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, '~an array of two elements~i'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":52:{a:3:{i:0;s:3:"foo";i:1;a:1:{i:1;s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, '~an array of two elements~i'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":40:{a:2:{i:0;i:100;i:1;a:1:{i:1;s:3:"bar";}}}', \TypeError::class, '~must be of type string~i'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":41:{a:2:{i:0;s:0:"";i:1;a:1:{i:1;s:3:"bar";}}}', \DomainException::class, '~cannot be an empty string~i'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, '~must be of type array~i'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":50:{a:2:{i:0;s:3:"foo";i:1;a:1:{s:3:"baz";s:3:"bar";}}}', \TypeError::class, '~index must be of type int~i'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":45:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:-1;s:3:"bar";}}}', \OutOfRangeException::class, '~negative index~i'],

            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":39:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;i:42;}}}', \TypeError::class, '~must be of type string~i'],
            ['C:30:"Bytemap\\Benchmark\\ArrayBytemap":43:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;s:2:"ba";}}}', \DomainException::class, '~value must be exactly~i'],

            // DsDequeBytemap
            ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, '~an array of two elements~i'],

            ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, '~must be a Ds\\\\Deque~i'],
            ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":68:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:2:"ba";}}}', \TypeError::class, '~must be a Ds\\\\Deque~i'],

            // DsVectorBytemap
            ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, '~an array of two elements~i'],

            ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, '~must be a Ds\\\\Vector~i'],
            ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":68:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:2:"ba";}}}', \TypeError::class, '~must be a Ds\\\\Vector~i'],

            // C:28:"Bytemap\\Benchmark\\SplBytemap":69:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":69:{a:2:{i:0;s:2:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}', \UnexpectedValueException::class, '~error at offset~i'],

            ['C:28:"Bytemap\\Benchmark\\SplBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, '~an array of two elements~i'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":77:{a:3:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, '~an array of two elements~i'],

            ['C:28:"Bytemap\\Benchmark\\SplBytemap":65:{a:2:{i:0;i:100;i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}', \TypeError::class, '~must be of type string~i'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":66:{a:2:{i:0;s:0:"";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:3:"bar";}}}', \DomainException::class, '~cannot be an empty string~i'],

            ['C:28:"Bytemap\\Benchmark\\SplBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, '~must be an SplFixedArray~i'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":65:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}}}', \TypeError::class, '~must be an SplFixedArray~i'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":64:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;i:42;}}}', \TypeError::class, '~must be of type string~i'],
            ['C:28:"Bytemap\\Benchmark\\SplBytemap":68:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:2:"ba";}}}', \DomainException::class, '~value must be exactly~i'],

            // C:15:"Bytemap\\Bytemap":37:{a:2:{i:0;s:3:"foo";i:1;s:6:"foobar";}}
            ['C:15:"Bytemap\\Bytemap":37:{a:2:{i:0;s:2:"foo";i:1;s:6:"foobar";}}', \UnexpectedValueException::class, '~error at offset~i'],

            ['C:15:"Bytemap\\Bytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, '~an array of two elements~i'],
            ['C:15:"Bytemap\\Bytemap":45:{a:3:{i:0;s:3:"foo";i:1;s:6:"foobar";i:2;b:1;}}', \UnexpectedValueException::class, '~an array of two elements~i'],

            ['C:15:"Bytemap\\Bytemap":33:{a:2:{i:0;i:100;i:1;s:6:"foobar";}}', \TypeError::class, '~must be of type string~i'],
            ['C:15:"Bytemap\\Bytemap":34:{a:2:{i:0;s:0:"";i:1;s:6:"foobar";}}', \DomainException::class, '~cannot be an empty string~i'],

            ['C:15:"Bytemap\\Bytemap":44:{a:2:{i:0;s:3:"foo";i:1;a:1:{i:1;s:3:"bar";}}}', \TypeError::class, '~must be of type string~i'],
            ['C:15:"Bytemap\\Bytemap":36:{a:2:{i:0;s:3:"foo";i:1;s:5:"fooba";}}', \DomainException::class, '~not a multiple~i'],
        ];

        if (\extension_loaded('ds')) {
            yield from [
                // C:32:"Bytemap\Benchmark\DsDequeBytemap":64:{a:2:{i:0;s:3:"foo";i:1;C:8:"Ds\Deque":20:{s:3:"foo";s:3:"bar";}}}
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":64:{a:2:{i:0;s:2:"foo";i:1;C:8:"Ds\\Deque":20:{s:3:"foo";s:3:"bar";}}}', \UnexpectedValueException::class, '~error at offset~i'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":72:{a:3:{i:0;s:3:"foo";i:1;C:8:"Ds\\Deque":20:{s:3:"foo";s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, '~an array of two elements~i'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":59:{a:2:{i:0;i:100;i:1;C:8:"Ds\\Deque":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, '~must be of type string~i'],
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":60:{a:2:{i:0;s:0:"";i:1;C:8:"Ds\\Deque":19:{s:3:"foo";i:1;i:42;}}}', \DomainException::class, '~cannot be an empty string~i'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":63:{a:2:{i:0;s:3:"foo";i:1;C:8:"Ds\\Deque":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, '~must be of type string~i'],
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":63:{a:2:{i:0;s:3:"foo";i:1;C:8:"Ds\\Deque":19:{s:3:"foo";s:2:"ba";}}}', \DomainException::class, '~value must be exactly~i'],

                // C:33:"Bytemap\\Benchmark\\DsVectorBytemap":65:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}}}
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":65:{a:2:{i:0;s:2:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}}}', \UnexpectedValueException::class, '~error at offset~i'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":73:{a:3:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, '~an array of two elements~i'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":60:{a:2:{i:0;i:100;i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, '~must be of type string~i'],
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":61:{a:2:{i:0;s:0:"";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \DomainException::class, '~cannot be an empty string~i'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":64:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, '~must be of type string~i'],
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":64:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";s:2:"ba";}}}', \DomainException::class, '~value must be exactly~i'],
            ];
        } else {
            yield from [
                // C:32:"Bytemap\Benchmark\DsDequeBytemap":130:{a:2:{i:0;s:3:"foo";i:1;O:8:"Ds\Deque":2:{s:15:"Ds\Dequearray";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:18:"Ds\Dequecapacity";i:8;}}}
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":130:{a:2:{i:0;s:2:"foo";i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}}}', \UnexpectedValueException::class, '~error at offset~i'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":138:{a:3:{i:0;s:3:"foo";i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}i:2;b:1;}}', \UnexpectedValueException::class, '~an array of two elements~i'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":126:{a:2:{i:0;i:100;i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}}}', \TypeError::class, '~must be of type string~i'],
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":127:{a:2:{i:0;s:0:"";i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}}}', \DomainException::class, '~cannot be an empty string~i'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":125:{a:2:{i:0;s:3:"foo";i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;i:42;}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}}}', \TypeError::class, '~must be of type string~i'],
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":129:{a:2:{i:0;s:3:"foo";i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:2:"ba";}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}}}', \DomainException::class, '~value must be exactly~i'],

                // C:33:"Bytemap\\Benchmark\\DsVectorBytemap":133:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"Ds\\Vectorarray";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"Ds\\Vectorcapacity";i:8;}}}
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":133:{a:2:{i:0;s:2:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \UnexpectedValueException::class, '~error at offset~i'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":141:{a:3:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}i:2;b:1;}}', \UnexpectedValueException::class, '~an array of two elements~i'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":129:{a:2:{i:0;i:100;i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \TypeError::class, '~must be of type string~i'],
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":130:{a:2:{i:0;s:0:"";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \DomainException::class, '~cannot be an empty string~i'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":128:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;i:42;}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \TypeError::class, '~must be of type string~i'],
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":132:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:2:"ba";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \DomainException::class, '~value must be exactly~i'],
            ];
        }
    }

    public static function serializableInstanceProvider(): \Generator
    {
        yield from self::jsonSerializableInstanceProvider();
    }

    // `BytemapTest`

    /**
     * @covers \Bytemap\AbstractBytemap
     * @dataProvider implementationProvider
     */
    public function testConstructorEmptyString(string $impl): void
    {
        $this->expectException(\DomainException::class);
        self::instantiate($impl, '');
    }

    public static function invalidLengthAtInsertionProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['b', 'd', 'f'],
                ['bd', 'df', 'gg'],
            ] as $elements) {
                foreach (self::generateElementsOfInvalidLength(\strlen($elements[0])) as $invalidElement) {
                    $emptyBytemap = self::instantiate($impl, $elements[0]);
                    yield from self::generateInvalidElements($emptyBytemap, \array_fill(0, 6, $elements[0]), $invalidElement);
                }
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::insert
     * @dataProvider invalidLengthAtInsertionProvider
     */
    public function testInsertionOfInvalidLength(
        BytemapInterface $bytemap,
        array $elements,
        string $invalidElement,
        bool $useGenerator,
        array $sequence,
        array $inserted,
        int $firstIndex
    ): void {
        $expectedSequence = [];
        foreach ($sequence as $index => $key) {
            $expectedSequence[$index] = $elements[$key];
        }
        $generator = (static function () use ($elements, $inserted, $invalidElement) {
            foreach ($inserted as $key) {
                yield null === $key ? $invalidElement : $elements[$key];
            }
        })();

        try {
            $bytemap->insert($useGenerator ? $generator : \iterator_to_array($generator), $firstIndex);
        } catch (\DomainException $e) {
        }
        self::assertTrue(isset($e), 'Failed asserting that exception of type "\\DomainException" is thrown.');
        self::assertSequence($expectedSequence, $bytemap);
    }

    /**
     * @covers \Bytemap\AbstractBytemap::grep
     * @dataProvider implementationProvider
     */
    public function testGreppingInvalidPatterns(string $impl): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Empty regular expression');
        self::instantiate($impl, "\x0")->grep(['~x~', '', '~y~'])->rewind();
    }

    public static function greppingProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            $elements = ['z', 'x', 'y', 'w', 'u', 't'];
            foreach ([
                [[], ['~~'], false, 1, null, []],
                [[], ['~~'], true, 1, null, []],
                [[], ['~x~'], false, 1, null, []],
                [[], ['~x~'], true, 1, null, []],

                [[1], [], false, 1, null, [1]],
                [[1], [], true, 1, null, []],
                [[1], ['~x~'], false, 1, null, []],
                [[1], ['~x~'], true, 1, null, [1]],
                [[1], ['~x~', '~X~'], false, 1, null, []],
                [[1], ['~x~', '~X~'], true, 1, null, [1]],
                [[1], ['~X~', '~z~'], false, 1, null, [1]],
                [[1], ['~X~', '~z~'], true, 1, null, []],
                [[1], ['~X~'], true, 1, null, []],
                [[1], ['~X~i'], true, 1, null, [1]],
                [[1], ['~z~'], false, 1, null, [1]],
                [[1], ['~z~'], true, 1, null, []],

                [[1, 2, 3, 1], ['~x~'], false, -2, null, [2 => 3, 1 => 2]],
                [[1, 2, 3, 1], ['~x~'], false, -1, null, [2 => 3]],
                [[1, 2, 3, 1], ['~x~'], false, 0, null, []],
                [[1, 2, 3, 1], ['~x~'], false, 1, null, [1 => 2]],
                [[1, 2, 3, 1], ['~x~'], false, 2, null, [1 => 2, 2 => 3]],

                [[1, 2, 3, 1], ['~x~'], true, -2, null, [3 => 1, 0 => 1]],
                [[1, 2, 3, 1], ['~x~'], true, -1, null, [3 => 1]],
                [[1, 2, 3, 1], ['~x~'], true, 0, null, []],
                [[1, 2, 3, 1], ['~x~'], true, 1, null, [0 => 1]],
                [[1, 2, 3, 1], ['~x~'], true, 2, null, [0 => 1, 3 => 1]],

                [[1, 2, 3, null, 1], ['~[axz]~'], false, \PHP_INT_MAX, null, [1 => 2, 2 => 3]],
                [[1, 2, 3, null, 1], ['~[axz]~'], true, \PHP_INT_MAX, null, [0 => 1, 3 => 0, 4 => 1]],
                [[1, 2, 3, null, 1], ['~[^axz]~'], true, \PHP_INT_MAX, null, [1 => 2, 2 => 3]],

                [[1, 2, 3, null, 1], ['~xy~'], true, \PHP_INT_MAX, null, []],

                [[1, 2, 3, null, 1], ['~^y|z$~'], true, \PHP_INT_MAX, null, [1 => 2, 3 => 0]],

                [[1, 2, 3, null, 1], ['~(?<=z)x~'], true, \PHP_INT_MAX, null, []],
                [[1, 2, 3, null, 1], ['~x(?=y)~'], true, \PHP_INT_MAX, null, []],

                [[1, 2, 3, null, 1], ['~~'], true, \PHP_INT_MAX, null, [1, 2, 3, 0, 1]],

                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, null, [1, 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, 0, [1 => 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, 2, [3 => 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, -2, [6 => 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, -7, [1 => 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, 6, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, 42, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, -42, [1, 2, 3, 4, 5, 1, 2]],

                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, null, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, 0, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, 2, [1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, -2, [4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, -7, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, 6, [5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, 42, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, -42, []],
            ] as [$subject, $patterns, $whitelist, $howMany, $startAfter, $expected]) {
                yield [$impl, $elements, $subject, $patterns, $whitelist, $howMany, $startAfter, $expected];
            }

            $elements = ['zx', 'xy', 'yy', 'wy', 'wx', 'tu'];
            foreach ([
                [[], ['~~'], false, 1, null, []],
                [[], ['~~'], true, 1, null, []],
                [[], ['~x~'], false, 1, null, []],
                [[], ['~x~'], true, 1, null, []],

                [[1], [], false, 1, null, [1]],
                [[1], [], true, 1, null, []],
                [[1], ['~xy~'], false, 1, null, []],
                [[1], ['~xy~'], true, 1, null, [1]],
                [[1], ['~xy~', '~Xy~'], false, 1, null, []],
                [[1], ['~xy~', '~Xy~'], true, 1, null, [1]],
                [[1], ['~Xy~', '~zx~'], false, 1, null, [1]],
                [[1], ['~Xy~', '~zx~'], true, 1, null, []],
                [[1], ['~Xy~'], true, 1, null, []],
                [[1], ['~Xy~i'], true, 1, null, [1]],
                [[1], ['~zx~'], false, 1, null, [1]],
                [[1], ['~zx~'], true, 1, null, []],

                [[1, 2, 3, 1], ['~xy~'], false, -2, null, [2 => 3, 1 => 2]],
                [[1, 2, 3, 1], ['~xy~'], false, -1, null, [2 => 3]],
                [[1, 2, 3, 1], ['~xy~'], false, 0, null, []],
                [[1, 2, 3, 1], ['~xy~'], false, 1, null, [1 => 2]],
                [[1, 2, 3, 1], ['~xy~'], false, 2, null, [1 => 2, 2 => 3]],

                [[1, 2, 3, 1], ['~xy~'], true, -2, null, [3 => 1, 0 => 1]],
                [[1, 2, 3, 1], ['~xy~'], true, -1, null, [3 => 1]],
                [[1, 2, 3, 1], ['~xy~'], true, 0, null, []],
                [[1, 2, 3, 1], ['~xy~'], true, 1, null, [0 => 1]],
                [[1, 2, 3, 1], ['~xy~'], true, 2, null, [0 => 1, 3 => 1]],

                [[1, 2, 3, null, 1], ['~[axz]~'], false, \PHP_INT_MAX, null, [1 => 2, 2 => 3]],
                [[1, 2, 3, null, 1], ['~[axz]~'], true, \PHP_INT_MAX, null, [0 => 1, 3 => 0, 4 => 1]],
                [[1, 2, 3, null, 1], ['~[^axz]~'], true, \PHP_INT_MAX, null, [0 => 1, 1 => 2, 2 => 3, 4 => 1]],

                [[1, 2, 3, null, 1], ['~xyy~'], true, \PHP_INT_MAX, null, []],

                [[1, 2, 3, null, 1], ['~^yy|xy$~'], true, \PHP_INT_MAX, null, [0 => 1, 1 => 2, 4 => 1]],
                [[1, 2, 3, null, 1], ['~y$~'], true, \PHP_INT_MAX, null, [0 => 1, 1 => 2, 2 => 3, 4 => 1]],

                [[1, 2, 3, null, 1], ['~(?<=z)x~'], true, \PHP_INT_MAX, null, [3 => 0]],
                [[1, 2, 3, null, 1], ['~(?!<x)y~'], true, \PHP_INT_MAX, null, [0 => 1, 1 => 2, 2 => 3, 4 => 1]],
                [[1, 2, 3, null, 1], ['~x(?=y)~'], true, \PHP_INT_MAX, null, [0 => 1, 4 => 1]],
                [[1, 2, 3, 4, 1], ['~w(?!y)~'], true, \PHP_INT_MAX, null, [3 => 4]],

                [[1, 2, 3, null, 1], ['~yy|zx~'], true, \PHP_INT_MAX, null, [1 => 2, 3 => 0]],

                [[1, 2, 3, null, 1], ['~~'], true, \PHP_INT_MAX, null, [1, 2, 3, 0, 1]],

                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, null, [1, 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, 0, [1 => 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, 2, [3 => 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, -2, [6 => 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, -7, [1 => 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, 6, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, 42, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, -42, [1, 2, 3, 4, 5, 1, 2]],

                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, null, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, 0, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, 2, [1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, -2, [4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, -7, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, 6, [5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, 42, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, -42, []],

                [[1, 2, 3, 4, 5, 1, 2], (static function (): \Generator {
                    yield from ['~x~', '~z~'];
                })(), true, 7, null, [1, 3 => 4, 5 => 1]],
            ] as [$subject, $patterns, $whitelist, $howMany, $startAfter, $expected]) {
                yield [$impl, $elements, $subject, $patterns, $whitelist, $howMany, $startAfter, $expected];
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::grep
     * @dataProvider greppingProvider
     */
    public function testGrepping(
        string $impl,
        array $elements,
        array $subject,
        iterable $patterns,
        bool $whitelist,
        int $howMany,
        ?int $startAfter,
        array $expected
        ): void {
        $expectedSequence = [];
        foreach ($expected as $index => $key) {
            $expectedSequence[$index] = $elements[$key];
        }
        $bytemap = self::instantiate($impl, $elements[0]);
        foreach ($subject as $index => $key) {
            if (null !== $key) {
                $bytemap[$index] = $elements[$key];
            }
        }
        self::assertSame($expectedSequence, \iterator_to_array($bytemap->grep($patterns, $whitelist, $howMany, $startAfter)));
    }

    public static function implementationDirectionProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([true, false] as $forward) {
                yield [$impl, $forward];
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::grep
     * @depends testGrepping
     * @dataProvider implementationDirectionProvider
     */
    public function testGreppingCircularLookup(string $impl, bool $forward): void
    {
        $bytemap = self::instantiate($impl, "\x0\x0\x0");

        // The number of unique elements should exceed `AbstractBytemap::GREP_MAXIMUM_LOOKUP_SIZE`.
        for ($element = 'aaa'; $element <= 'pzz'; ++$element) {  // 16 * 26 * 26 = 10816 elements.
            $bytemap[] = $element;
        }
        $bytemap[0] = 'akk';
        $bytemap[] = 'akk';
        $pattern = '~(?<![c-d])(?<=[a-f])([k-p])(?=\\1)(?![m-n])~';  // [abef](kk|ll|oo|pp)
        $matchCount = 0;
        foreach ($bytemap->grep([$pattern], true, $forward ? \PHP_INT_MAX : -\PHP_INT_MAX) as $element) {
            ++$matchCount;
            if (1 === $matchCount) {
                $bytemap[1] = 'akk';
            }
        }
        self::assertSame(18, $matchCount);
    }

    public static function implementationProvider(): \Generator
    {
        foreach ([
            ArrayBytemap::class,
            DsDequeBytemap::class,
            DsVectorBytemap::class,
            SplBytemap::class,
            Bytemap::class,
        ] as $impl) {
            yield [$impl];
        }
    }

    protected static function instantiate(string $impl, ...$args): BytemapInterface
    {
        return new $impl(...$args);
    }

    // `DeletionTestTrait`
    protected static function deletionInstanceProvider(): \Generator
    {
        yield from self::insertionInstanceProvider();
    }

    // `FindingTestTrait`
    protected static function seekableInstanceProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['z', 'x', 'y', 'w', 'u', 't'],
                ['zx', 'xy', 'yy', 'wy', 'ut', 'tu'],
            ] as $elements) {
                yield [self::instantiate($impl, $elements[0]), $elements];
            }
        }
    }

    protected static function seekableWithDefaultFirstProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['z', 'x'],
                ['zx', 'xy'],
            ] as $elements) {
                yield [self::instantiate($impl, $elements[0]), $elements];
            }
        }
    }

    // `InsertionTestTrait`
    protected static function insertionInstanceProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['z', 'x', 'y', 'w', 'u', 't'],
                ['zx', 'xy', 'yy', 'wy', 'ut', 'tu'],
            ] as $elements) {
                yield [self::instantiate($impl, $elements[0]), $elements];
            }
        }
    }
}

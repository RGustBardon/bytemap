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
 * @covers \Bytemap\Benchmark\AbstractDsBytemap
 * @covers \Bytemap\Benchmark\ArrayBytemap
 * @covers \Bytemap\Benchmark\DsDequeBytemap
 * @covers \Bytemap\Benchmark\DsVectorBytemap
 * @covers \Bytemap\Benchmark\SplBytemap
 * @covers \Bytemap\Bytemap
 */
final class NativeFunctionalityTest extends AbstractTestOfBytemap
{
    use ArrayAccessTestTrait;
    use CloneableTestTrait;
    use CountableTestTrait;
    use InvalidLengthTestTrait;
    use IterableTestTrait;
    use JsonSerializableTestTrait;
    use MagicPropertiesTestTrait;
    use SerializableTestTrait;

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

    // `MagicPropertiesTestTrait`
    public static function magicPropertiesInstanceProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            yield [self::instantiate($impl, 'a')];
        }
    }

    // `SerializableTestTrait`
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

            // DsDequeBytemap
            ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, 'an array of two elements'],

            ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, 'must be a Ds\\Deque'],
            ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":68:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:2:"ba";}}}', \TypeError::class, 'must be a Ds\\Deque'],

            // DsVectorBytemap
            ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":20:{a:1:{i:0;s:3:"foo";}}', \UnexpectedValueException::class, 'an array of two elements'],

            ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"foo";}}', \TypeError::class, 'must be a Ds\\Vector'],
            ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":68:{a:2:{i:0;s:3:"foo";i:1;O:13:"SplFixedArray":2:{i:0;N;i:1;s:2:"ba";}}}', \TypeError::class, 'must be a Ds\\Vector'],

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
                // C:32:"Bytemap\Benchmark\DsDequeBytemap":64:{a:2:{i:0;s:3:"foo";i:1;C:8:"Ds\Deque":20:{s:3:"foo";s:3:"bar";}}}
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":64:{a:2:{i:0;s:2:"foo";i:1;C:8:"Ds\\Deque":20:{s:3:"foo";s:3:"bar";}}}', \UnexpectedValueException::class, 'error at offset'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":72:{a:3:{i:0;s:3:"foo";i:1;C:8:"Ds\\Deque":20:{s:3:"foo";s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":59:{a:2:{i:0;i:100;i:1;C:8:"Ds\\Deque":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":60:{a:2:{i:0;s:0:"";i:1;C:8:"Ds\\Deque":19:{s:3:"foo";i:1;i:42;}}}', \DomainException::class, 'cannot be an empty string'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":63:{a:2:{i:0;s:3:"foo";i:1;C:8:"Ds\\Deque":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":63:{a:2:{i:0;s:3:"foo";i:1;C:8:"Ds\\Deque":19:{s:3:"foo";s:2:"ba";}}}', \DomainException::class, 'value must be exactly'],

                // C:33:"Bytemap\\Benchmark\\DsVectorBytemap":65:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}}}
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":65:{a:2:{i:0;s:2:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}}}', \UnexpectedValueException::class, 'error at offset'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":73:{a:3:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":20:{s:3:"foo";s:3:"bar";}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":60:{a:2:{i:0;i:100;i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":61:{a:2:{i:0;s:0:"";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \DomainException::class, 'cannot be an empty string'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":64:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";i:1;i:42;}}}', \TypeError::class, 'must be of type string'],
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":64:{a:2:{i:0;s:3:"foo";i:1;C:9:"Ds\\Vector":19:{s:3:"foo";s:2:"ba";}}}', \DomainException::class, 'value must be exactly'],
            ];
        } else {
            yield from [
                // C:32:"Bytemap\Benchmark\DsDequeBytemap":130:{a:2:{i:0;s:3:"foo";i:1;O:8:"Ds\Deque":2:{s:15:"Ds\Dequearray";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:18:"Ds\Dequecapacity";i:8;}}}
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":130:{a:2:{i:0;s:2:"foo";i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}}}', \UnexpectedValueException::class, 'error at offset'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":138:{a:3:{i:0;s:3:"foo";i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":126:{a:2:{i:0;i:100;i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}}}', \TypeError::class, 'must be of type string'],
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":127:{a:2:{i:0;s:0:"";i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}}}', \DomainException::class, 'cannot be an empty string'],

                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":125:{a:2:{i:0;s:3:"foo";i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;i:42;}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}}}', \TypeError::class, 'must be of type string'],
                ['C:32:"Bytemap\\Benchmark\\DsDequeBytemap":129:{a:2:{i:0;s:3:"foo";i:1;O:8:"Ds\\Deque":2:{s:15:"'."\x0".'Ds\\Deque'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:2:"ba";}s:18:"'."\x0".'Ds\\Deque'."\x0".'capacity";i:8;}}}', \DomainException::class, 'value must be exactly'],

                // C:33:"Bytemap\\Benchmark\\DsVectorBytemap":133:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"Ds\\Vectorarray";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"Ds\\Vectorcapacity";i:8;}}}
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":133:{a:2:{i:0;s:2:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \UnexpectedValueException::class, 'error at offset'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":141:{a:3:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}i:2;b:1;}}', \UnexpectedValueException::class, 'an array of two elements'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":129:{a:2:{i:0;i:100;i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \TypeError::class, 'must be of type string'],
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":130:{a:2:{i:0;s:0:"";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \DomainException::class, 'cannot be an empty string'],

                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":128:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;i:42;}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \TypeError::class, 'must be of type string'],
                ['C:33:"Bytemap\\Benchmark\\DsVectorBytemap":132:{a:2:{i:0;s:3:"foo";i:1;O:9:"Ds\\Vector":2:{s:16:"'."\x0".'Ds\\Vector'."\x0".'array";a:2:{i:0;s:3:"foo";i:1;s:2:"ba";}s:19:"'."\x0".'Ds\\Vector'."\x0".'capacity";i:8;}}}', \DomainException::class, 'value must be exactly'],
            ];
        }
    }

    public static function serializableInstanceProvider(): \Generator
    {
        yield from self::jsonSerializableInstanceProvider();
    }

    // `NativeFunctionalityTest`

    /**
     * @covers \Bytemap\AbstractBytemap
     * @dataProvider implementationProvider
     * @expectedException \DomainException
     */
    public function testConstructorEmptyString(string $impl): void
    {
        self::instantiate($impl, '');
    }
}

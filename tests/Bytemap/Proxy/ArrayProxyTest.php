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

namespace Bytemap\Proxy;

use Bytemap\ArrayAccessTestTrait;
use Bytemap\Bytemap;
use Bytemap\BytemapInterface;
use Bytemap\IterableTestTrait;
use Bytemap\MagicPropertiesTestTrait;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 * @covers \Bytemap\Proxy\AbstractProxy
 * @covers \Bytemap\Proxy\ArrayProxy
 */
final class ArrayProxyTest extends AbstractTestOfProxy
{
    use ArrayAccessTestTrait;
    use IterableTestTrait;
    use MagicPropertiesTestTrait;

    private const WALK_NO_USERDATA = 'void';

    private $originalCollation;

    protected function setUp(): void
    {
        $this->originalCollation = \setlocale(\LC_COLLATE, '0');
    }

    protected function tearDown(): void
    {
        \setlocale(\LC_COLLATE, $this->originalCollation);
    }

    // `ArrayAccessPropertiesTrait`
    public static function arrayAccessInstanceProvider(): \Generator
    {
        foreach ([
            ['b', 'd', 'f'],
            ['bd', 'df', 'gg'],
        ] as $elements) {
            $defaultValue = \str_repeat("\x0", \strlen($elements[0]));
            yield [new ArrayProxy($defaultValue, ...$elements), $defaultValue, $elements];
        }
    }

    // `IterableTestTrait`
    public static function iterableInstanceProvider(): \Generator
    {
        foreach ([
            ['b', 'd', 'f'],
            ['bd', 'df', 'gg'],
        ] as $elements) {
            yield [new ArrayProxy(\str_repeat("\x0", \strlen($elements[0]))), $elements];
        }
    }

    // `MagicPropertiesTestTrait`
    public static function magicPropertiesInstanceProvider(): \Generator
    {
        yield [new ArrayProxy('a')];
    }

    public function testConstructor(): void
    {
        $elements = ['cd', 'xy', 'ef', 'ef'];
        self::assertSame($elements, self::instantiate(...$elements)->exportArray());
    }

    public function testCloning(): void
    {
        $elements = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$elements);
        $clone = clone $arrayProxy;
        self::assertNotSame($arrayProxy, $clone);
        $clone[] = 'bb';
        self::assertSame($elements, $arrayProxy->exportArray());
        self::assertSame(\array_merge($elements, ['bb']), $clone->exportArray());
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Error at offset
     */
    public function testUnserializeErrorAtOffset(): void
    {
        // C:24:"Bytemap\Proxy\ArrayProxy":62:{C:15:"Bytemap\Bytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}}}
        \unserialize('C:24:"Bytemap\\Proxy\\ArrayProxy":61:{C:15:"Bytemap\\Bytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}}}');
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage expected a bytemap
     */
    public function testUnserializeUnexpectedType(): void
    {
        // C:24:"Bytemap\Proxy\ArrayProxy":62:{C:15:"Bytemap\Bytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}}}
        \unserialize('C:24:"Bytemap\\Proxy\\ArrayProxy":20:{a:1:{i:0;s:3:"foo";}}');
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage default value
     */
    public function testUnserializeInvalidElementLength(): void
    {
        // C:24:"Bytemap\Proxy\ArrayProxy":62:{C:15:"Bytemap\Bytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}}}
        \unserialize('C:24:"Bytemap\\Proxy\\ArrayProxy":61:{C:15:"Bytemap\\Bytemap":33:{a:2:{i:0;s:2:"fo";i:1;s:3:"bar";}}}');
    }

    public function testSerializable(): void
    {
        $elements = ['cd', 'xy', 'cd', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$elements);
        $arrayProxy[6] = 'bb';
        $defaultValue = $arrayProxy[5];
        unset($arrayProxy[6]);
        unset($arrayProxy[5]);
        $unserializedArrayProxy = \unserialize(\serialize($arrayProxy), ['allowed_classes' => [ArrayProxy::class]]);
        self::assertSame($elements, $unserializedArrayProxy->exportArray());
        $unserializedArrayProxy[6] = 'bb';
        self::assertSame($defaultValue, $unserializedArrayProxy[5]);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage default value
     */
    public function testUnserializeUnexpectedValue(): void
    {
        // C:24:"Bytemap\Proxy\ArrayProxy":62:{C:15:"Bytemap\Bytemap":34:{a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}}}
        \unserialize('C:24:"Bytemap\\Proxy\\ArrayProxy":61:{C:15:"Bytemap\\Bytemap":33:{a:2:{i:0;s:2:"fo";i:1;s:3:"bar";}}}');
    }

    public function testWrapUnwrap(): void
    {
        $elements = ['cd', 'xy', 'ef', 'ef'];
        $bytemap = new Bytemap('ab');
        $bytemap->insert($elements);
        $arrayProxy = self::instantiate()::wrap($bytemap);
        self::assertSame($elements, $arrayProxy->exportArray());

        $arrayProxy[] = 'bb';
        self::assertSame($bytemap, $arrayProxy->unwrap());
    }

    public function testExportArray(): void
    {
        $elements = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public function testImport(): void
    {
        $arrayProxy = self::instantiate()::import('cd', ['xy', 2 => 'ef']);
        self::assertSame(['xy', 'ef'], $arrayProxy->exportArray());
    }

    /**
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage Size parameter expected to be greater than 0
     */
    public function testChunkSizeNotPositive(): void
    {
        self::instantiate('cd', 'xy', 'ef', 'ef')->chunk(0, false)->rewind();
    }

    public function testChunk(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef', 'bb');
        self::assertSame([
            ['cd', 'xy'],
            ['ef', 'ef'],
            ['bb'],
        ], \iterator_to_array($arrayProxy->chunk(2, false)));
        self::assertSame([
            [0 => 'cd', 1 => 'xy'],
            [2 => 'ef', 3 => 'ef'],
            [4 => 'bb'],
        ], \iterator_to_array($arrayProxy->chunk(2, true)));
    }

    public function testCountValues(): void
    {
        self::assertSame([], self::instantiate()->countValues());
        self::assertSame([
            'cd' => 1,
            'xy' => 2,
            'ef' => 2,
        ], self::instantiate('cd', 'xy', 'ef', 'ef', 'xy')->countValues());
    }

    public static function diffProvider(): \Generator
    {
        foreach ([
            [[], [], []],
            [[], [['cd']], []],
            [['cd', 'xy', 'ef', 'ef'], [], ['cd', 'xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], [['bb']], ['cd', 'xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], [['cd'], ['ef']], [1 => 'xy']],
            [['cd', 'xy', 'ef', 'ef'], [['ef', 'cd']], [1 => 'xy']],
        ] as [$elements, $iterables, $expected]) {
            yield [$elements, $iterables, $expected];
        }
    }

    /**
     * @dataProvider diffProvider
     */
    public function testDiff(array $elements, array $iterables, array $expected): void
    {
        $arrayProxy = new ArrayProxy('ab', ...$elements);
        self::assertSame($expected, \iterator_to_array($arrayProxy->diff(...$iterables)));
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public static function filterProvider(): \Generator
    {
        foreach ([
            [["\x00", '0', '1', '2'], null, 0, ["\x00", 2 => '1', '2']],
            [["\x00\x00", '00', '11', '22'], null, 0, ["\x00\x00", '00', '11', '22']],
            [
                ['cd', 'xy', 'ef', 'ef', 'bb'],
                function (string $element): bool {
                    return 'ef' !== $element;
                },
                0,
                ['cd', 'xy', 4 => 'bb'],
            ],
            [
                ['cd', 'xy', 'ef', 'ef', 'bb'],
                function (int $key): bool {
                    return 3 !== $key;
                },
                \ARRAY_FILTER_USE_KEY,
                ['cd', 'xy', 'ef', 4 => 'bb'],
            ],
            [
                ['cd', 'xy', 'ef', 'ef', 'bb'],
                function (string $element, int $key): bool {
                    return 'ef' !== $element && 1 !== $key;
                },
                \ARRAY_FILTER_USE_BOTH,
                ['cd', 4 => 'bb'],
            ],
        ] as [$elements, $callable, $flag, $expected]) {
            yield [$elements, $callable, $flag, $expected];
        }
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilter(array $elements, ?callable $callback, int $flag, array $expected): void
    {
        $arrayProxy = new ArrayProxy($elements[0], ...$elements);
        self::assertSame($expected, \iterator_to_array($arrayProxy->filter($callback, $flag)));
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public static function flipProvider(): \Generator
    {
        foreach ([
            [[], []],
            [['cd'], [['cd', 0]]],
            [['cd', 'xy', 'ef', 'ef'], [['cd', 0], ['xy', 1], ['ef', 2], ['ef', 3]]],
        ] as [$elements, $expected]) {
            yield [$elements, $expected];
        }
    }

    /**
     * @dataProvider flipProvider
     */
    public function testFlip(array $elements, array $expected): void
    {
        $arrayProxy = new ArrayProxy($elements[0] ?? 'cd', ...$elements);
        $actual = [];
        foreach ($arrayProxy->flip() as $element => $index) {
            $actual[] = [$element, $index];
        }
        self::assertSame($expected, $actual);
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public function testInArray(): void
    {
        self::assertFalse(self::instantiate()->inArray('ab'));
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertFalse($arrayProxy->inArray('ab'));
        self::assertTrue($arrayProxy->inArray('ef'));
    }

    public static function intersectProvider(): \Generator
    {
        foreach ([
            [[], [], []],
            [['a'], [], ['a']],
            [['a'], [[]], []],
            [[], [['a']], []],
            [['a'], [['a']], ['a']],
            [['a'], [['b']], []],
            [['a'], [['a'], ['a']], ['a']],
            [['a'], [['a'], ['b']], []],
            [['100'], [['100']], ['100']],
            [['100'], [[100]], ['100']],
            [['100'], [[100.]], ['100']],
            [['100'], [[1e2]], ['100']],
            [['100'], [['100.']], []],
            [['100'], [['1e2']], []],
            [["\u{2010}"], [["\u{2011}"]], []],
            [['a', 'b', 'c', 'x', 'a'], [['x', 'a', 'b', 'd'], ['a', 'x', 'b', 'f']], ['a', 'b', 3 => 'x', 'a']],
            [['100', '1e2', "\u{2010}", "\u{2011}"], [["\u{2010}", '100', '1e2', "\u{2011}"]], ['100', '1e2', "\u{2010}", "\u{2011}"]],
        ] as [$elements, $iterables, $expected]) {
            yield [$elements, $iterables, $expected];
        }
    }

    /**
     * @dataProvider intersectProvider
     */
    public function testIntersect(array $elements, array $iterables, array $expected): void
    {
        $arrayProxy = new ArrayProxy($elements[0] ?? 'a', ...$elements);
        self::assertSame($expected, \iterator_to_array($arrayProxy->intersect(...$iterables)));
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public function testKeyExists(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertFalse($arrayProxy->keyExists(-1));
        self::assertTrue($arrayProxy->keyExists(0));
        self::assertTrue($arrayProxy->keyExists(3));
        self::assertFalse($arrayProxy->keyExists(4));
    }

    /**
     * @expectedException \UnderflowException
     */
    public function testKeyFirstEmptyBytemap(): void
    {
        self::instantiate()->keyFirst();
    }

    public function testKeyFirst(): void
    {
        self::assertSame(0, self::instantiate('cd', 'xy', 'ef', 'ef')->keyFirst());
    }

    /**
     * @expectedException \UnderflowException
     */
    public function testKeyLastEmptyBytemap(): void
    {
        self::instantiate()->keyLast();
    }

    public function testKeyLast(): void
    {
        self::assertSame(3, self::instantiate('cd', 'xy', 'ef', 'ef')->keyLast());
    }

    public function testKeys(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertSame([0, 1, 2, 3], \iterator_to_array($arrayProxy->keys()));
        self::assertSame([], \iterator_to_array($arrayProxy->keys('ab')));
        self::assertSame([2, 3], \iterator_to_array($arrayProxy->keys('ef')));
    }

    public static function mapProvider(): \Generator
    {
        $defaultCallback = function (?string ...$args): string {
            return \implode(':', \array_map('strval', $args));
        };

        foreach (['array', \Iterator::class, \IteratorAggregate::class] as $iterableType) {
            foreach ([
                [['cd', 'xy', 'ef', 'ef'], null, [], ['cd', 'xy', 'ef', 'ef']],
                [
                    ['cd', 'xy', 'ef', 'ef'],
                    null,
                    [['i1', 'i2']],
                    [['cd', 'i1'], ['xy', 'i2'], ['ef', null], ['ef', null]],
                ],
                [
                    ['cd', 'xy', 'ef', 'ef'],
                    null,
                    [['i1', 'i2', 'i3', 'i4', 'i5']],
                    [['cd', 'i1'], ['xy', 'i2'], ['ef', 'i3'], ['ef', 'i4'], [null, 'i5']],
                ],
                [
                    ['cd', 'xy', 'ef', 'ef'],
                    null,
                    [['i1', 'i2'], ['a1']],
                    [['cd', 'i1', 'a1'], ['xy', 'i2', null], ['ef', null, null], ['ef', null, null]],
                ],
                [
                    ['cd', 'xy', 'ef', 'ef'],
                    $defaultCallback,
                    [['i1', 'i2']],
                    ['cd:i1', 'xy:i2', 'ef:', 'ef:'],
                ],
                [
                    ['cd', 'xy', 'ef', 'ef'],
                    $defaultCallback,
                    [['i1', 'i2', 'i3', 'i4', 'i5']],
                    ['cd:i1', 'xy:i2', 'ef:i3', 'ef:i4', ':i5'],
                ],
                [
                    ['cd', 'xy', 'ef', 'ef'],
                    $defaultCallback,
                    [['i1', 'i2'], ['a1']],
                    ['cd:i1:a1', 'xy:i2:', 'ef::', 'ef::'],
                ],
                [
                    ['cd', 'xy', 'ef', 'ef'],
                    function (string $input): string {
                        return \strtoupper($input);
                    },
                    [],
                    ['CD', 'XY', 'EF', 'EF'],
                ],
            ] as [$elements, $callback, $iterables, $expected]) {
                switch ($iterableType) {
                    case \Iterator::class:
                        foreach ($iterables as $key => $iterable) {
                            $iterables[$key] = (function (array $iterable) {
                                yield from $iterable;
                            })($iterable);
                        }

                        break;
                    case \IteratorAggregate::class:
                        foreach ($iterables as $key => $iterable) {
                            $iterables[$key] = new class($iterable) implements \IteratorAggregate {
                                private $it;

                                public function __construct(array $iterable)
                                {
                                    $this->it = $iterable;
                                }

                                public function getIterator(): \Iterator
                                {
                                    return new \ArrayIterator($this->it);
                                }
                            };
                        }

                        break;
                    default:
                        break;
                }

                yield [$elements, $callback, $iterables, $expected];
            }
        }
    }

    /**
     * @dataProvider mapProvider
     */
    public function testMap(array $elements, ?callable $callback, array $iterables, array $expected): void
    {
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame($expected, \iterator_to_array($arrayProxy->map($callback, ...$iterables)));
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public function testMerge(): void
    {
        $elements = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$elements);
        $array = ['a1', 'a2', 'a3'];
        $bytemap = new Bytemap('cd');
        $bytemap->insert(['b1', 'b2']);
        $generator = function (): \Generator {
            yield from ['g1', 'g2', 'g3'];
        };
        self::assertSame([
            'cd', 'xy', 'ef', 'ef',
            'a1', 'a2', 'a3',
            'b1', 'b2',
            'g1', 'g2', 'g3',
        ], \iterator_to_array($arrayProxy->merge($array, $bytemap, $generator())));
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    /**
     * @expectedException \TypeError
     */
    public function testMultiSortTypeError(): void
    {
        $number = 42;
        self::instantiate('cd', 'ef')->multiSort(\SORT_STRING, true, $number);
    }

    /**
     * @expectedException \TypeError
     */
    public function testMultiSortInstanceError(): void
    {
        $heap = new \SplMaxHeap();
        $heap->insert('xy');
        $heap->insert('zz');
        self::instantiate('cd', 'ef')->multiSort(\SORT_STRING, true, $heap);
    }

    /**
     * @expectedException \UnderflowException
     */
    public function testMultiSortUnderflowException(): void
    {
        $array = ['xy'];
        self::instantiate('cd', 'ef')->multiSort(\SORT_STRING, true, $array);
    }

    public static function multiSortProvider(): \Generator
    {
        foreach (self::sortProvider() as [$defaultValue, $elements, $sortFlagsClosure, $expectedBeforeOrientation]) {
            $iterables = [\array_map('\\strval', \range(10, 10 * \count($elements), 10))];

            $expectedIterableBeforeOrientation = [];
            foreach ($expectedBeforeOrientation as $value) {
                if (null !== $value) {
                    $value = $iterables[0][\array_search($value, $elements, true)];
                }
                $expectedIterableBeforeOrientation[] = $value;
            }

            foreach ([false, true] as $ascending) {
                $iterables[1] = new Bytemap('00');
                $iterables[1]->insert($iterables[0]);

                $iterables[2] = new \Ds\Deque();
                $iterables[2]->push(...$iterables[0]);

                $iterables[3] = new \Ds\Vector();
                $iterables[3]->push(...$iterables[0]);

                $iterables[4] = \SplFixedArray::fromArray($iterables[0]);

                $expected = $ascending ? $expectedBeforeOrientation : \array_reverse($expectedBeforeOrientation);
                $expectedIterable = $ascending ? $expectedIterableBeforeOrientation : \array_reverse($expectedIterableBeforeOrientation);

                yield [$defaultValue, $elements, $sortFlagsClosure, $ascending, $expected, $iterables, $expectedIterable];
            }
        }

        $sortFlagsClosure = static function (): int { return \SORT_STRING; };
        yield from [
            ['cd', ['cd'], $sortFlagsClosure, true, ['cd'], [], []],
            ['cd', [], $sortFlagsClosure, true, [], [], []],
            ['cd', ['cd', 'ab', 'xy'], $sortFlagsClosure, true, ['ab', 'cd', 'xy'], [['foo' => 10, 20, 'bar' => 30]], [20, 'foo' => 10, 'bar' => 30]],
        ];
    }

    /**
     * @dataProvider multiSortProvider
     */
    public function testMultiSort(
        string $defaultValue,
        array $elements,
        \Closure $sortFlagsClosure,
        bool $ascending,
        array $expected,
        array $iterables,
        array $expectedIterable
    ): void {
        $arrayProxy = new ArrayProxy($defaultValue, ...$elements);
        $arrayProxy->multiSort($sortFlagsClosure($this), $ascending, ...$iterables);
        self::assertArrayMask($expected, $arrayProxy->exportArray());
        foreach ($iterables as $iterable) {
            $array = null;
            if (\is_array($iterable)) {
                $array = $iterable;
            } elseif (\is_object($iterable)) {
                if ($iterable instanceof BytemapInterface) {
                    $array = [];
                    foreach ($iterable as $element) {
                        $array[] = $element;
                    }
                } elseif ($iterable instanceof \Ds\Collection || $iterable instanceof \SplFixedArray) {
                    $array = $iterable->toArray();
                }
            }
            \assert(null !== $array);
            self::assertArrayMask($expectedIterable, $array);
        }
    }

    public function testNatCaseSort(): void
    {
        $arrayProxy = new ArrayProxy("\x0\x0\x0", '12 ', '100', "\u{d6} ", 'PPP', 'ooo');

        try {
            $arrayProxy->natCaseSort();
            self::assertSame(['12 ', '100', 'ooo', 'PPP', "\u{d6} "], $arrayProxy->exportArray());
        } catch (\RuntimeException $e) {
            self::markTestSkipped($e->getMessage());
        }
    }

    public function testNatSort(): void
    {
        $arrayProxy = new ArrayProxy("\x0\x0\x0", '12 ', '100', "\u{d6} ", 'PPP', 'ooo');

        try {
            $arrayProxy->natSort();
            self::assertSame(['12 ', '100', 'PPP', 'ooo', "\u{d6} "], $arrayProxy->exportArray());
        } catch (\RuntimeException $e) {
            self::markTestSkipped($e->getMessage());
        }
    }

    public function testPad(): void
    {
        $elements = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame($elements, \iterator_to_array($arrayProxy->pad(2, 'bb')));
        self::assertSame($elements + [4 => 'bb', 5 => 'bb'], \iterator_to_array($arrayProxy->pad(6, 'bb')));
        self::assertSame(\array_merge(['bb', 'bb'], $elements), \iterator_to_array($arrayProxy->pad(-6, 'bb')));
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    /**
     * @expectedException \UnderflowException
     */
    public function testPopEmptyBytemap(): void
    {
        self::instantiate()->pop();
    }

    public function testPop(): void
    {
        $arrayProxy = self::instantiate('ef', 'cd', 'xy');
        self::assertSame('xy', $arrayProxy->pop());
        self::assertSame(['ef', 'cd'], $arrayProxy->exportArray());
    }

    public function testPush(): void
    {
        $arrayProxy = self::instantiate();
        self::assertSame(2, $arrayProxy->push('cd', 'xy'));
        self::assertSame(['cd', 'xy'], $arrayProxy->exportArray());
        self::assertSame(4, $arrayProxy->push('ef', 'ef'));
        self::assertSame(['cd', 'xy', 'ef', 'ef'], $arrayProxy->exportArray());
    }

    /**
     * @expectedException \UnderflowException
     * @expectedExceptionMessage Iterable is empty
     */
    public function testRandEmpty(): void
    {
        self::instantiate()->rand();
    }

    /**
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage between 1 and the number of elements
     */
    public function testRandNegative(): void
    {
        self::instantiate('cd', 'xy')->rand(-1);
    }

    /**
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage between 1 and the number of elements
     */
    public function testRandOverflow(): void
    {
        self::instantiate('cd', 'xy')->rand(3);
    }

    public function testRand(): void
    {
        $arrayProxy = new ArrayProxy('cd', ...\array_fill(0, 1000, 'ab'));

        $singles = [$arrayProxy->rand(), $arrayProxy->rand(), $arrayProxy->rand()];
        self::assertTrue(\count(\array_unique($singles)) > 1);
        foreach ($singles as $key) {
            self::assertTrue(isset($arrayProxy[$key]));
        }

        $batch = $arrayProxy->rand(3);
        self::assertTrue(\count(\array_unique($batch)) > 1);
        foreach ($batch as $key) {
            self::assertTrue(isset($arrayProxy[$key]));
        }

        self::assertNotSame($singles, $batch);

        self::assertSame(\range(0, \count($arrayProxy) - 1), $arrayProxy->rand(\count($arrayProxy)));
    }

    public function testReduce(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertSame('barcd2xy2ef2ef2', $arrayProxy->reduce(static function (string $initial, string $element): string {
            return $initial.$element.\strlen($element);
        }, 'bar'));
    }

    public static function replaceProvider(): \Generator
    {
        foreach ([
            [
                ['cd'],
                [],
                ['cd'],
            ],
            [
                ['cd', 'xy', 'ef', 'ef'],
                [['a1', 'a2'], ['b1', 'b2', 'b3'], [4 => 'c1']],
                ['b1', 'b2', 'b3', 'ef', 'c1'],
            ],
        ] as [$elements, $iterables, $expected]) {
            yield [$elements, $iterables, $expected];
        }
    }

    /**
     * @dataProvider replaceProvider
     */
    public function testReplace(array $elements, array $iterables, array $expected): void
    {
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame($expected, $arrayProxy->replace(...$iterables)->exportArray());
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public function testReverse(): void
    {
        $elements = ['cd', 'xy', 'ef', 'ef', 'bb'];
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame(['bb', 'ef', 'ef', 'xy', 'cd'], \iterator_to_array($arrayProxy->reverse(false)));
        self::assertSame([4 => 'bb', 3 => 'ef', 2 => 'ef', 1 => 'xy', 0 => 'cd'], \iterator_to_array($arrayProxy->reverse(true)));
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public static function sortProvider(): \Generator
    {
        $defaultValue = "\x0\x0\x0";
        $elements = ['12 ', '100', "\u{d6} ", 'PPP', 'ooo'];

        foreach ([
            \SORT_REGULAR => ['100', '12 ', 'PPP', 'ooo', "\u{d6} "],
            \SORT_NUMERIC => [null, null, null, '12 ', '100'],
            \SORT_STRING => ['100', '12 ', 'PPP', 'ooo', "\u{d6} "],
            \SORT_STRING | \SORT_FLAG_CASE => ['100', '12 ', 'ooo', 'PPP', "\u{d6} "],
        ] as $sortFlags => $expected) {
            yield [
                $defaultValue,
                $elements,
                static function () use ($sortFlags): int {
                    return $sortFlags;
                },
                $expected,
            ];
        }

        $errorMessage = self::getSortLocaleErrorMessage();
        if (null === $errorMessage) {
            $sortFlagsClosure = static function (): int {
                return \SORT_LOCALE_STRING;
            };
        } else {
            $sortFlagsClosure = static function () use ($errorMessage): int {
                self::markTestSkipped($errorMessage);
            };
        }

        yield [$defaultValue, $elements, $sortFlagsClosure, ['100', '12 ', "\u{d6} ", 'ooo', 'PPP']];

        $sortFlagsClosure = static function (): void {
            self::markTestSkipped('This test requires \\SORT_NATURAL and a callable \\strnatcmp');
        };

        if (\defined('\\SORT_NATURAL') && \is_callable('\\strnatcmp')) {
            $sortFlagsClosure = static function (): int {
                return \SORT_NATURAL;
            };
        }

        yield [$defaultValue, $elements, $sortFlagsClosure, ['12 ', '100', 'PPP', 'ooo', "\u{d6} "]];

        $sortFlagsClosure = static function (): void {
            self::markTestSkipped('This test requires \\SORT_NATURAL and a callable \\strnatbasecmp');
        };

        if (\defined('\\SORT_NATURAL') && \is_callable('\\strnatcasecmp')) {
            $sortFlagsClosure = static function (): int {
                return \SORT_NATURAL | \SORT_FLAG_CASE;
            };
        }

        yield [$defaultValue, $elements, $sortFlagsClosure, ['12 ', '100', 'ooo', 'PPP', "\u{d6} "]];
    }

    /**
     * @dataProvider sortProvider
     */
    public function testRSort(string $defaultValue, array $elements, \Closure $sortFlagsClosure, array $expected): void
    {
        $arrayProxy = new ArrayProxy($defaultValue, ...$elements);
        $arrayProxy->rSort($sortFlagsClosure());
        self::assertArrayMask(\array_reverse($expected), $arrayProxy->exportArray());
    }

    public function testSearch(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertFalse($arrayProxy->search('ab'));
        self::assertSame(1, $arrayProxy->search('xy'));
        self::assertSame(2, $arrayProxy->search('ef'));
    }

    /**
     * @expectedException \UnderflowException
     */
    public function testShiftEmptyBytemap(): void
    {
        self::instantiate()->shift();
    }

    public function testShift(): void
    {
        $arrayProxy = self::instantiate('ef', 'cd', 'xy');
        self::assertSame('ef', $arrayProxy->shift());
        self::assertSame(['cd', 'xy'], $arrayProxy->exportArray());
    }

    public function testShuffle(): void
    {
        $arrayProxy = new ArrayProxy('cd');
        for ($element = 'aa'; $element <= 'dv'; ++$element) {
            $arrayProxy[] = $element;
        }
        $sorted = $arrayProxy->exportArray();
        $arrayProxy->shuffle();
        $shuffled = $arrayProxy->exportArray();
        self::assertNotSame($sorted, $shuffled);
        \sort($shuffled, \SORT_STRING);
        self::assertSame($sorted, $shuffled);
    }

    public function testSizeOf(): void
    {
        self::assertSame(4, self::instantiate('cd', 'xy', 'ef', 'ef')->sizeOf());
    }

    public static function sliceProvider(): \Generator
    {
        foreach ([
            [[], 0, 0, false, []],
            [['cd', 'xy', 'ef', 'ef'], -3, null, false, ['xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 0, null, false, ['cd', 'xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 2, null, false, ['ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 2, 0, false, []],
            [['cd', 'xy', 'ef', 'ef'], -2, 1, false, ['ef']],
            [['cd', 'xy', 'ef', 'ef'], 0, 1, false, ['cd']],
            [['cd', 'xy', 'ef', 'ef'], 0, 3, false, ['cd', 'xy', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 1, 1, false, ['xy']],
            [['cd', 'xy', 'ef', 'ef'], 1, -1, false, ['xy', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 1, -1, true, [1 => 'xy', 'ef']],
        ] as [$elements, $index, $length, $preserveKeys, $expected]) {
            yield [$elements, $index, $length, $preserveKeys, $expected];
        }
    }

    /**
     * @dataProvider sliceProvider
     */
    public function testSlice(array $elements, int $index, ?int $length, bool $preserveKeys, array $expected): void
    {
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame($expected, \iterator_to_array($arrayProxy->slice($index, $length, $preserveKeys)));
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    /**
     * @dataProvider sortProvider
     */
    public function testSort(string $defaultValue, array $elements, \Closure $sortFlagsClosure, array $expected): void
    {
        $arrayProxy = new ArrayProxy($defaultValue, ...$elements);
        $arrayProxy->sort($sortFlagsClosure());
        self::assertArrayMask($expected, $arrayProxy->exportArray());
    }

    public static function spliceProvider(): \Generator
    {
        foreach ([
            [[], 0, 0, [], [], []],
            [['cd', 'xy', 'ef', 'ef'], -3, null, [], ['xy', 'ef', 'ef'], ['cd']],
            [['cd', 'xy', 'ef', 'ef'], 0, null, [], ['cd', 'xy', 'ef', 'ef'], []],
            [['cd', 'xy', 'ef', 'ef'], 2, null, [], ['ef', 'ef'], ['cd', 'xy']],
            [['cd', 'xy', 'ef', 'ef'], 2, 0, [], [], ['cd', 'xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], -2, 1, [], ['ef'], ['cd', 'xy', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 0, 1, [], ['cd'], ['xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 0, 3, [], ['cd', 'xy', 'ef'], ['ef']],
            [['cd', 'xy', 'ef', 'ef'], 1, 1, [], ['xy'], ['cd', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 1, -1, [], ['xy', 'ef'], ['cd', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 1, -1, ['rs', 'tu'], ['xy', 'ef'], ['cd', 'rs', 'tu', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 0, null, ['rs', 'tu'], ['cd', 'xy', 'ef', 'ef'], ['rs', 'tu']],
            [['cd', 'xy', 'ef', 'ef'], -1, null, 'rs', ['ef'], ['cd', 'xy', 'ef', 'rs']],
        ] as [$elements, $index, $length, $replacement, $expectedSlice, $expectedMutation]) {
            yield [$elements, $index, $length, $replacement, $expectedSlice, $expectedMutation];
        }
    }

    /**
     * @dataProvider spliceProvider
     *
     * @param mixed $replacement
     */
    public function testSplice(
        array $elements,
        int $index,
        ?int $length,
        $replacement,
        array $expectedSlice,
        array $expectedMutation
    ): void {
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame($expectedSlice, $arrayProxy->splice($index, $length, $replacement)->exportArray());
        self::assertSame($expectedMutation, $arrayProxy->exportArray());
    }

    /**
     * @expectedException \TypeError
     * @expectedExceptionMessage must be of type integer
     */
    public function testUnionStringKey(): void
    {
        self::instantiate()->union(['foo' => 'xy']);
    }

    /**
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage Negative index
     */
    public function testUnionNegativeKey(): void
    {
        self::instantiate()->union([-1 => 'xy']);
    }

    public static function unionProvider(): \Generator
    {
        foreach ([
            [
                ['cd'],
                [],
                ['cd'],
            ],
            [
                ['cd', 'xy', 'ef', 'ef'],
                [['a1', 'a2'], ['b1', 'b2', 'b3'], [4 => 'c1']],
                ['cd', 'xy', 'ef', 'ef', 'c1'],
            ],
            [
                ['cd', 'xy', 'ef', 'ef'],
                [[7 => 'c2', 4 => 'c1'], [9 => 'c4', 6 => 'c3']],
                ['cd', 'xy', 'ef', 'ef', 'c1', 'ab', 'ab', 'c2', 'ab', 'c4'],
            ],
        ] as [$elements, $iterables, $expected]) {
            yield [$elements, $iterables, $expected];
        }
    }

    /**
     * @dataProvider unionProvider
     */
    public function testUnion(array $elements, array $iterables, array $expected): void
    {
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame($expected, $arrayProxy->union(...$iterables)->exportArray());
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public static function uniqueProvider(): \Generator
    {
        $defaultValue = "\x0\x0\x0";
        $elements = ['100', '1e2', "\u{2010}", "\u{2011}"];

        foreach ([
            \SORT_REGULAR => ['100', 2 => "\u{2010}", "\u{2011}"],
            \SORT_NUMERIC => ['100', 2 => "\u{2010}"],
            \SORT_STRING => ['100', '1e2', "\u{2010}", "\u{2011}"],
        ] as $sortFlags => $expected) {
            yield [
                $defaultValue,
                $elements,
                function () use ($sortFlags): int {
                    return $sortFlags;
                },
                $expected,
            ];
        }

        $errorMessage = self::getUniqueLocaleErrorMessage();
        if (null === $errorMessage) {
            $sortFlagsClosure = static function (): int {
                return \SORT_LOCALE_STRING;
            };
        } else {
            $sortFlagsClosure = static function () use ($errorMessage): int {
                self::markTestSkipped($errorMessage);
            };
        }

        yield [$defaultValue, $elements, $sortFlagsClosure, ['100', '1e2', "\u{2010}"]];
    }

    /**
     * @dataProvider uniqueProvider
     */
    public function testUnique(string $defaultValue, array $elements, \Closure $sortFlagsClosure, array $expected): void
    {
        $arrayProxy = new ArrayProxy($defaultValue, ...$elements);
        self::assertSame($expected, \iterator_to_array($arrayProxy->unique($sortFlagsClosure())));
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public function testUnshift(): void
    {
        $arrayProxy = self::instantiate('ef', 'cd');
        self::assertSame(4, $arrayProxy->unshift('xy', 'cc'));
        self::assertSame(['xy', 'cc', 'ef', 'cd'], $arrayProxy->exportArray());
    }

    public function testUSort(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        $arrayProxy->uSort(function (string $a, string $b): int {
            static $weights = ['ef' => 0, 'xy' => 1, 'cd' => 2];

            return $weights[$a] <=> $weights[$b];
        });
        self::assertSame(['ef', 'ef', 'xy', 'cd'], $arrayProxy->exportArray());
    }

    public function testValues(): void
    {
        $elements = ['cd', 'xy', 'ef', 'ef'];
        self::assertSame($elements, \iterator_to_array(self::instantiate(...$elements)->values()));
    }

    /**
     * @expectedException \ArgumentCountError
     */
    public function testWalkTooFewArguments(): void
    {
        self::instantiate()->walk(function ($element, $index, $foo) {
        });
    }

    public static function walkMethod(&$element, $index, string $userdata = null): void
    {
        $element = \gettype($userdata)[0].$index;
    }

    public static function walkProvider(): \Generator
    {
        foreach ([
            [['cd', 'xy', 'ef', 'ef'], function ($element, $index) {
                $element = 'ab';
            }, self::WALK_NO_USERDATA, ['cd', 'xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], function (&$element, $index) {
                $element = \sprintf('%02d', $index);
            }, self::WALK_NO_USERDATA, ['00', '01', '02', '03']],
            [['cd', 'xy', 'ef', 'ef'], function (&$element, $index, string $userdata) {
                $element = \gettype($userdata)[0].$index;
            }, 'foo', ['s0', 's1', 's2', 's3']],
            [['cd', 'xy', 'ef', 'ef'], function (&$element, $index, string $userdata = null) {
                $element = \gettype($userdata)[0].$index;
            }, null, ['N0', 'N1', 'N2', 'N3']],
            [['cd', 'xy', 'ef', 'ef'], self::class.'::walkMethod', null, ['N0', 'N1', 'N2', 'N3']],
            [['cd', 'xy', 'ef', 'ef'], __NAMESPACE__.'\\transform', null, ['N0', 'N1', 'N2', 'N3']],
        ] as [$elements, $callback, $userdata, $expected]) {
            yield [$elements, $callback, $userdata, $expected];
        }
    }

    /**
     * @dataProvider walkProvider
     *
     * @param mixed $userdata
     */
    public function testWalk(array $elements, callable $callback, $userdata, array $expected): void
    {
        $arrayProxy = self::instantiate(...$elements);
        if (self::WALK_NO_USERDATA === $userdata) {
            $arrayProxy->walk($callback);
        } else {
            $arrayProxy->walk($callback, $userdata);
        }
        self::assertSame($expected, $arrayProxy->exportArray());
    }

    /**
     * @expectedException \UnderflowException
     * @expectedExceptionMessage equal number of elements
     */
    public function testCombineCardinalityMismatch(): void
    {
        self::instantiate()::combine('cd', [1, 6, 3], ['ab', 'ef']);
    }

    public function testCombine(): void
    {
        $arrayProxy = self::instantiate()::combine('cd', [1, 6, 3], ['ab', 'ef', 'xy']);
        self::assertSame(['cd', 'ab', 'cd', 'xy', 'cd', 'cd', 'ef'], $arrayProxy->exportArray());
    }

    /**
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage index can't be negative
     */
    public function testFillNegativeIndex(): void
    {
        self::instantiate()::fill('cd', -2, 3);
    }

    /**
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage elements can't be negative
     */
    public function testFillNegativeCardinality(): void
    {
        self::instantiate()::fill('cd', 2, -3);
    }

    public function testFill(): void
    {
        $arrayProxy = self::instantiate()::fill('cd', 0, 3);
        self::assertSame(['cd', 'cd', 'cd'], $arrayProxy->exportArray());
    }

    public static function fillKeysProvider(): \Generator
    {
        foreach ([
            ['cd', [1, 6, 3], null, ['cd', 'cd', 'cd', 'cd', 'cd', 'cd', 'cd']],
            ['cd', [1, 6, 3], 'ab', ['cd', 'ab', 'cd', 'ab', 'cd', 'cd', 'ab']],
        ] as [$defaultValue, $keys, $value, $expected]) {
            yield [$defaultValue, $keys, $value, $expected];
        }
    }

    /**
     * @dataProvider fillKeysProvider
     */
    public function testFillKeys(string $defaultValue, iterable $keys, ?string $value, array $expected): void
    {
        $arrayProxy = self::instantiate()::fillKeys('cd', [1, 6, 3], null);
        self::assertSame(['cd', 'cd', 'cd', 'cd', 'cd', 'cd', 'cd'], $arrayProxy->exportArray());

        $arrayProxy = self::instantiate()::fillKeys('cd', [1, 6, 3], 'ab');
        self::assertSame(['cd', 'ab', 'cd', 'ab', 'cd', 'cd', 'ab'], $arrayProxy->exportArray());
    }

    public static function implodeJoinProvider(): \Generator
    {
        foreach ([
            [[], '', ''],
            [[], ',', ''],
            [['cd'], '', 'cd'],
            [['cd'], ',', 'cd'],
            [['cd', 'xy', 'ef', 'ef'], '', 'cdxyefef'],
            [['cd', 'xy', 'ef', 'ef'], ',', 'cd,xy,ef,ef'],
        ] as [$elements, $glue, $expected]) {
            yield [$elements, $glue, $expected];
        }
    }

    /**
     * @dataProvider implodeJoinProvider
     */
    public function testImplodeJoin(array $elements, string $glue, string $expected): void
    {
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame($expected, $arrayProxy->implode($glue));
        self::assertSame($expected, $arrayProxy->join($glue));
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public static function pregFilterProvider(): \Generator
    {
        foreach ([
            [['cd', 'xy', 'ef', 'ef'], [], 'z', -1, [], 0],
            [['cd', 'xy', 'ef', 'ef'], '~[cdf]~', 'z', -1, ['zz', 2 => 'ez', 'ez'], 4],
            [['cd', 'xy', 'ef', 'ef'], '~[cdf]~', 'z', 0, ['cd', 2 => 'ef', 'ef'], 0],
            [['cd', 'xy', 'ef', 'ef'], '~[cdf]~', 'z', 1, ['zd', 2 => 'ez', 'ez'], 3],
            [['cd', 'xy', 'ef', 'ef'], '~[cdf]~', 'z', 2, ['zz', 2 => 'ez', 'ez'], 4],
            [['cd', 'xy', 'ef', 'ef'], ['~c~', '~d|f~'], 'z', 2, ['zz', 2 => 'ez', 'ez'], 4],
            [['cd', 'xy', 'ef', 'ef'], ['~c~', '~d|f~'], 'abc', 2, ['abcabc', 2 => 'eabc', 'eabc'], 4],
            [['cd', 'xy', 'ef', 'ef'], ['~c~', '~i~'], ['i', 'j'], 2, ['jd'], 2],
            [['cd', 'xy', 'ef', 'ef'], ['~c~', '~i~'], [], 2, ['d'], 1],
            [['cd', 'xy', 'ef', 'ef'], '~(x)(y)~', '${2}${1}', 2, [1 => 'yx'], 1],
        ] as [$elements, $pattern, $replacement, $limit, $expectedResult, $expectedCount]) {
            yield [$elements, $pattern, $replacement, $limit, $expectedResult, $expectedCount];
        }
    }

    /**
     * @dataProvider pregFilterProvider
     *
     * @param mixed $pattern
     * @param mixed $replacement
     */
    public function testPregFilter(array $elements, $pattern, $replacement, int $limit, array $expectedResult, int $expectedCount): void
    {
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame($expectedResult, \iterator_to_array($arrayProxy->pregFilter($pattern, $replacement, $limit, $count)));
        self::assertSame($expectedCount, $count);
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public static function pregGrepProvider(): \Generator
    {
        foreach ([
            [[], '~ab~', 0, []],
            [['ab', 'cc', 'cd'], '~^c+~', 0, [1 => 'cc', 'cd']],
            [['ab', 'cc', 'cd'], '~^c+~', \PREG_GREP_INVERT, ['ab']],
        ] as [$elements, $pattern, $flags, $expected]) {
            yield [$elements, $pattern, $flags, $expected];
        }
    }

    /**
     * @dataProvider pregGrepProvider
     */
    public function testPregGrep(array $elements, string $pattern, int $flags, array $expected): void
    {
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame($expected, \iterator_to_array($arrayProxy->pregGrep($pattern, $flags)));
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public static function pregReplaceProvider(): \Generator
    {
        foreach ([
            [['cd', 'xy', 'ef', 'ef'], [], 'z', -1, ['cd', 'xy', 'ef', 'ef'], 0],
            [['cd', 'xy', 'ef', 'ef'], '~[cdf]~', 'z', -1, ['zz', 'xy', 'ez', 'ez'], 4],
            [['cd', 'xy', 'ef', 'ef'], '~[cdf]~', 'z', 0, ['cd', 'xy', 'ef', 'ef'], 0],
            [['cd', 'xy', 'ef', 'ef'], '~[cdf]~', 'z', 1, ['zd', 'xy', 'ez', 'ez'], 3],
            [['cd', 'xy', 'ef', 'ef'], '~[cdf]~', 'z', 2, ['zz', 'xy', 'ez', 'ez'], 4],
            [['cd', 'xy', 'ef', 'ef'], ['~c~', '~d|f~'], 'z', 2, ['zz', 'xy', 'ez', 'ez'], 4],
            [['cd', 'xy', 'ef', 'ef'], ['~c~', '~d|f~'], 'abc', 2, ['abcabc', 'xy', 2 => 'eabc', 'eabc'], 4],
            [['cd', 'xy', 'ef', 'ef'], ['~c~', '~i~'], ['i', 'j'], 2, ['jd', 'xy', 'ef', 'ef'], 2],
            [['cd', 'xy', 'ef', 'ef'], ['~c~', '~i~'], [], 2, ['d', 'xy', 'ef', 'ef'], 1],
            [['cd', 'xy', 'ef', 'ef'], '~(x)(y)~', '${2}${1}', 2, ['cd', 'yx', 'ef', 'ef'], 1],
        ] as [$elements, $pattern, $replacement, $limit, $expectedResult, $expectedCount]) {
            yield [$elements, $pattern, $replacement, $limit, $expectedResult, $expectedCount];
        }
    }

    /**
     * @dataProvider pregReplaceProvider
     *
     * @param mixed $pattern
     * @param mixed $replacement
     */
    public function testPregReplace(array $elements, $pattern, $replacement, int $limit, array $expectedResult, int $expectedCount): void
    {
        $arrayProxy = self::instantiate(...$elements);
        self::assertSame($expectedResult, \iterator_to_array($arrayProxy->pregReplace($pattern, $replacement, $limit, $count)));
        self::assertSame($expectedCount, $count);
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public function pregReplaceCallbackProvider(): \Generator
    {
        $defaultCallback = function (array $matches): string {
            return $matches[0].',';
        };

        foreach ([
            [
                ['cd', 'xy', 'ef', 'ef', 'zz'],
                [],
                $defaultCallback,
                -1,
                ['cd', 'xy', 'ef', 'ef', 'zz'],
                0,
            ],
            [
                ['cd', 'xy', 'ef', 'ef', 'zz'],
                (function (): \Generator {
                    yield '~[ef]~';
                })(),
                $defaultCallback,
                -1,
                ['cd', 'xy', 'e,f,', 'e,f,', 'zz'],
                4,
            ],
            [
                ['cd', 'xy', 'ef', 'ef', 'zz'],
                ['~^c~', '~[ef]~'],
                function (array $matches): string {
                    return $matches[0].',';
                },
                1,
                ['c,d', 'xy', 'e,f', 'e,f', 'zz'],
                3,
            ],
        ] as [$elements, $pattern, $callback, $limit, $expectedResult, $expectedCount]) {
            yield [$elements, $pattern, $callback, $limit, $expectedResult, $expectedCount];
        }
    }

    /**
     * @dataProvider pregReplaceCallbackProvider
     *
     * @param mixed $pattern
     */
    public function testPregReplaceCallback(
        array $elements,
        $pattern,
        callable $callback,
        int $limit,
        array $expectedResult,
        int $expectedCount
    ): void {
        $arrayProxy = self::instantiate(...$elements);
        $result = $arrayProxy->pregReplaceCallback($pattern, $callback, $limit, $count);
        self::assertSame($expectedResult, \iterator_to_array($result));
        self::assertSame($expectedCount, $count);
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    public function pregReplaceCallbackArrayProvider(): \Generator
    {
        foreach ([
            [
                ['cd', 'xy', 'ef', 'ef', 'zz'],
                [],
                1,
                [],
                0,
            ],
            [
                ['cd', 'xy', 'ef', 'ef', 'zz'],
                [
                    '~^c~' => function (array $matches): string {
                        return 'g';
                    },
                ],
                1,
                ['gd', 'xy', 'ef', 'ef', 'zz'],
                1,
            ],
            [
                ['cd', 'xy', 'ef', 'ef', 'zz'],
                (function (): \Generator {
                    yield '~[ef]~' => function (array $matches): string {
                        return 'w';
                    };
                })(),
                -1,
                ['cd', 'xy', 'ww', 'ww', 'zz'],
                4,
            ],
            [
                ['cd', 'xy', 'ef', 'ef', 'zz'],
                [
                    '~^c~' => function (array $matches): string {
                        return 'g';
                    },
                    '~^g~' => function (array $matches): string {
                        return 'hi';
                    },
                    '~[ef]~' => function (array $matches): string {
                        return 'w';
                    },
                ],
                1,
                ['hid', 'xy', 'wf', 'wf', 'zz'],
                4,
            ],
        ] as [$elements, $patternsAndCallbacks, $limit, $expectedResult, $expectedCount]) {
            yield [$elements, $patternsAndCallbacks, $limit, $expectedResult, $expectedCount];
        }
    }

    /**
     * @dataProvider pregReplaceCallbackArrayProvider
     */
    public function testPregReplaceCallbackArray(array $elements, iterable $patternsAndCallbacks, int $limit, array $expectedResult, int $expectedCount): void
    {
        $arrayProxy = self::instantiate(...$elements);
        $result = $arrayProxy->pregReplaceCallbackArray($patternsAndCallbacks, $limit, $count);
        self::assertSame($expectedResult, \iterator_to_array($result));
        self::assertSame($expectedCount, $count);
        self::assertSame($elements, $arrayProxy->exportArray());
    }

    private static function assertArrayMask(array $arrayMask, array $array): void
    {
        self::assertCount(\count($arrayMask), $array);
        foreach ($arrayMask as $key => &$value) {
            if (null === $value) {
                $value = $array[$key] ?? '';
            }
        }
        self::assertSame(\serialize($arrayMask), \serialize($array));
    }

    private static function getLocaleErrorMessage(): ?string
    {
        if (!\defined('\\SORT_LOCALE_STRING') || !\is_callable('\\strcoll')) {
            return 'This test requires \\SORT_LOCALE_STRING and a callable \\strcoll';
        }

        $locale = null;
        foreach (['de_DE.utf8', 'de_DE.UTF-8'] as $locale) {
            if (false !== \setlocale(\LC_COLLATE, $locale)) {
                return null;
            }
        }

        return 'This test requires a '.$locale.' locale';
    }

    private static function getSortLocaleErrorMessage(): ?string
    {
        $errorMessage = self::getLocaleErrorMessage();
        if (null !== $errorMessage) {
            return $errorMessage;
        }

        $elements = ['12 ', '100', "\u{d6} ", 'PPP', 'ooo'];
        \sort($elements, \SORT_LOCALE_STRING);
        if ($elements !== ['100', '12 ', "\u{d6} ", 'ooo', 'PPP']) {
            return 'This test requires \\strcoll to work as expected';
        }

        return null;
    }

    private static function getUniqueLocaleErrorMessage(): ?string
    {
        $errorMessage = self::getLocaleErrorMessage();
        if (null !== $errorMessage) {
            return $errorMessage;
        }

        $elements = ["\u{2010}", "\u{2011}"];
        \sort($elements, \SORT_LOCALE_STRING);
        if ($elements !== ["\u{2010}"]) {
            return 'This test requires a locale to collate U+2010 and U+2011 together';
        }

        return null;
    }

    private static function instantiate(string ...$elements): ArrayProxyInterface
    {
        return new ArrayProxy('ab', ...$elements);
    }
}

function transform(&$element, $index, string $userdata = null): void
{
    $element = \gettype($userdata)[0].$index;
}

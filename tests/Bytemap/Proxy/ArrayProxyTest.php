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

use Bytemap\Bytemap;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 * @covers \Bytemap\Proxy\ArrayProxy
 */
final class ArrayProxyTest extends AbstractTestOfProxy
{
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

    public function testConstructor(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        self::assertSame($values, self::instantiate(...$values)->exportArray());
    }

    public function testClone(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$values);
        $clone = clone $arrayProxy;
        self::assertNotSame($arrayProxy, $clone);
        $clone[] = 'bb';
        self::assertSame($values, $arrayProxy->exportArray());
        self::assertSame(\array_merge($values, ['bb']), $clone->exportArray());
    }

    public function testWrapUnwrap(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        $bytemap = new Bytemap('ab');
        $bytemap->insert($values);
        $arrayProxy = self::instantiate()::wrap($bytemap);
        self::assertSame($values, $arrayProxy->exportArray());

        $arrayProxy[] = 'bb';
        self::assertSame($bytemap, $arrayProxy->unwrap());
    }

    public function testExportArray(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$values);
        self::assertSame($values, $arrayProxy->exportArray());
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

    public static function filterProvider(): \Generator
    {
        foreach ([
            [["\x00", '0', '1', '2'], null, 0, ["\x00", 2 => '1', '2']],
            [["\x00\x00", '00', '11', '22'], null, 0, ["\x00\x00", '00', '11', '22']],
            [
                ['cd', 'xy', 'ef', 'ef', 'bb'],
                function (string $item): bool {
                    return 'ef' !== $item;
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
                function (string $item, int $key): bool {
                    return 'ef' !== $item && 1 !== $key;
                },
                \ARRAY_FILTER_USE_BOTH,
                ['cd', 4 => 'bb'],
            ],
        ] as [$items, $callable, $flag, $expected]) {
            yield [$items, $callable, $flag, $expected];
        }
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilter(array $items, ?callable $callback, int $flag, array $expected): void
    {
        $arrayProxy = new ArrayProxy($items[0], ...$items);
        self::assertSame($expected, \iterator_to_array($arrayProxy->filter($callback, $flag)));
        self::assertSame($items, $arrayProxy->exportArray());
    }

    public function testInArray(): void
    {
        self::assertFalse(self::instantiate()->inArray('ab'));
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertFalse($arrayProxy->inArray('ab'));
        self::assertTrue($arrayProxy->inArray('ef'));
    }

    public function testKeyExists(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertFalse($arrayProxy->keyExists(-1));
        self::assertTrue($arrayProxy->keyExists(0));
        self::assertTrue($arrayProxy->keyExists(3));
        self::assertFalse($arrayProxy->keyExists(4));
    }

    public function testKeyFirst(): void
    {
        self::assertNull(self::instantiate()->keyFirst());
        self::assertSame(0, self::instantiate('cd', 'xy', 'ef', 'ef')->keyFirst());
    }

    public function testKeyLast(): void
    {
        self::assertNull(self::instantiate()->keyLast());
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
        $callback = function (?string ...$args): string {
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
                    $callback,
                    [['i1', 'i2']],
                    ['cd:i1', 'xy:i2', 'ef:', 'ef:'],
                ],
                [
                    ['cd', 'xy', 'ef', 'ef'],
                    $callback,
                    [['i1', 'i2', 'i3', 'i4', 'i5']],
                    ['cd:i1', 'xy:i2', 'ef:i3', 'ef:i4', ':i5'],
                ],
                [
                    ['cd', 'xy', 'ef', 'ef'],
                    $callback,
                    [['i1', 'i2'], ['a1']],
                    ['cd:i1:a1', 'xy:i2:', 'ef::', 'ef::'],
                ],
            ] as [$items, $callback, $iterables, $expected]) {
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

                yield [$items, $callback, $iterables, $expected];
            }
        }
    }

    /**
     * @dataProvider mapProvider
     *
     * @param mixed $items
     */
    public function testMap(array $items, ?callable $callback, array $iterables, array $expected): void
    {
        $arrayProxy = self::instantiate(...$items);
        self::assertSame($expected, \iterator_to_array($arrayProxy->map($callback, ...$iterables)));
        self::assertSame($items, $arrayProxy->exportArray());
    }

    public function testMerge(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$values);
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
        ], $arrayProxy->merge($array, $bytemap, $generator())->exportArray());
        self::assertSame($values, $arrayProxy->exportArray());
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
        $values = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$values);
        self::assertSame($values, $arrayProxy->pad(2, 'bb')->exportArray());
        self::assertSame($values + [4 => 'bb', 5 => 'bb'], $arrayProxy->pad(6, 'bb')->exportArray());
        self::assertSame(\array_merge(['bb', 'bb'], $values), $arrayProxy->pad(-6, 'bb')->exportArray());
        self::assertSame($values, $arrayProxy->exportArray());
    }

    public function testPop(): void
    {
        $arrayProxy = self::instantiate();
        self::assertNull($arrayProxy->pop());

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
        $arrayProxy = new ArrayProxy('cd', ...\array_fill_keys(\range(0, 999), 'ab'));

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
    }

    public function testReduce(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertSame('barcd2xy2ef2ef2', $arrayProxy->reduce(function (string $initial, string $value): string {
            return $initial.$value.\strlen($value);
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
        ] as [$items, $iterables, $expected]) {
            yield [$items, $iterables, $expected];
        }
    }

    /**
     * @dataProvider replaceProvider
     */
    public function testReplace(array $items, array $iterables, array $expected): void
    {
        $arrayProxy = self::instantiate(...$items);
        self::assertSame($expected, $arrayProxy->replace(...$iterables)->exportArray());
        self::assertSame($items, $arrayProxy->exportArray());
    }

    public function testReverse(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef', 'bb'];
        $arrayProxy = self::instantiate(...$values);
        self::assertSame(['bb', 'ef', 'ef', 'xy', 'cd'], $arrayProxy->reverse()->exportArray());
        self::assertSame($values, $arrayProxy->exportArray());
    }

    public static function sortProvider(): \Generator
    {
        $defaultItem = "\x0\x0\x0";
        $items = ['12 ', '100', "\u{d6} ", 'PPP', 'ooo'];

        foreach ([
            \SORT_REGULAR => ['100', '12 ', 'PPP', 'ooo', "\u{d6} "],
            \SORT_NUMERIC => [null, null, null, '12 ', '100'],
            \SORT_STRING => ['100', '12 ', 'PPP', 'ooo', "\u{d6} "],
            \SORT_STRING | \SORT_FLAG_CASE => ['100', '12 ', 'ooo', 'PPP', "\u{d6} "],
        ] as $sortFlags => $expected) {
            yield [
                $defaultItem,
                $items,
                function (self $that) use ($sortFlags): int {
                    return $sortFlags;
                },
                $expected,
            ];
        }

        $errorMessage = self::getSortLocaleErrorMessage();
        if (null === $errorMessage) {
            $sortFlagsClosure = function (self $that): int {
                return \SORT_LOCALE_STRING;
            };
        } else {
            $sortFlagsClosure = function (self $that) use ($errorMessage): int {
                $that->markTestSkipped($errorMessage);
            };
        }

        yield [$defaultItem, $items, $sortFlagsClosure, ['100', '12 ', "\u{d6} ", 'ooo', 'PPP']];

        $sortFlagsClosure = function (self $that): void {
            $that->markTestSkipped('This test requires \\SORT_NATURAL and a callable \\strnatcmp');
        };

        if (\defined('\\SORT_NATURAL') && \is_callable('\\strnatcmp')) {
            $sortFlagsClosure = function (self $that): int {
                return \SORT_NATURAL;
            };
        }

        yield [$defaultItem, $items, $sortFlagsClosure, ['12 ', '100', 'PPP', 'ooo', "\u{d6} "]];

        $sortFlagsClosure = function (self $that): void {
            $that->markTestSkipped('This test requires \\SORT_NATURAL and a callable \\strnatbasecmp');
        };

        if (\defined('\\SORT_NATURAL') && \is_callable('\\strnatcasecmp')) {
            $sortFlagsClosure = function (self $that): int {
                return \SORT_NATURAL | \SORT_FLAG_CASE;
            };
        }

        yield [$defaultItem, $items, $sortFlagsClosure, ['12 ', '100', 'ooo', 'PPP', "\u{d6} "]];
    }

    /**
     * @dataProvider sortProvider
     */
    public function testRSort(string $defaultItem, array $items, \Closure $sortFlagsClosure, array $expected): void
    {
        $arrayProxy = new ArrayProxy($defaultItem, ...$items);
        $arrayProxy->rSort($sortFlagsClosure($this));
        self::assertArrayMask(\array_reverse($expected), $arrayProxy->exportArray());
    }

    public function testSearch(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertFalse($arrayProxy->search('ab'));
        self::assertSame(1, $arrayProxy->search('xy'));
        self::assertSame(2, $arrayProxy->search('ef'));
    }

    public function testShift(): void
    {
        $arrayProxy = self::instantiate();
        self::assertNull($arrayProxy->shift());

        $arrayProxy = self::instantiate('ef', 'cd', 'xy');
        self::assertSame('ef', $arrayProxy->shift());
        self::assertSame(['cd', 'xy'], $arrayProxy->exportArray());
    }

    public function testShuffle(): void
    {
        $arrayProxy = new ArrayProxy('cd');
        for ($item = 'aa'; $item <= 'dv'; ++$item) {
            $arrayProxy[] = $item;
        }
        $sorted = $arrayProxy->exportArray();
        $arrayProxy->shuffle();
        $shuffled = $arrayProxy->exportArray();
        self::assertNotSame($sorted, $shuffled);
        \sort($shuffled, \SORT_STRING);
        self::assertSame($sorted, $shuffled);
    }

    public static function sliceProvider(): \Generator
    {
        foreach ([
            [[], 0, 0, []],
            [['cd', 'xy', 'ef', 'ef'], -3, null, ['xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 0, null, ['cd', 'xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 2, null, ['ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 2, 0, []],
            [['cd', 'xy', 'ef', 'ef'], -2, 1, ['ef']],
            [['cd', 'xy', 'ef', 'ef'], 0, 1, ['cd']],
            [['cd', 'xy', 'ef', 'ef'], 0, 3, ['cd', 'xy', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 1, 1, ['xy']],
            [['cd', 'xy', 'ef', 'ef'], 1, -1, ['xy', 'ef']],
        ] as [$items, $offset, $length, $expected]) {
            yield [$items, $offset, $length, $expected];
        }
    }

    /**
     * @dataProvider sliceProvider
     */
    public function testSlice(array $items, int $offset, ?int $length, array $expected): void
    {
        $arrayProxy = self::instantiate(...$items);
        self::assertSame($expected, $arrayProxy->slice($offset, $length)->exportArray());
        self::assertSame($items, $arrayProxy->exportArray());
    }

    /**
     * @dataProvider sortProvider
     */
    public function testSort(string $defaultItem, array $items, \Closure $sortFlagsClosure, array $expected): void
    {
        $arrayProxy = new ArrayProxy($defaultItem, ...$items);
        $arrayProxy->sort($sortFlagsClosure($this));
        self::assertArrayMask($expected, $arrayProxy->exportArray());
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
        ] as [$items, $iterables, $expected]) {
            yield [$items, $iterables, $expected];
        }
    }

    /**
     * @dataProvider unionProvider
     */
    public function testUnion(array $items, array $iterables, array $expected): void
    {
        $arrayProxy = self::instantiate(...$items);
        self::assertSame($expected, $arrayProxy->union(...$iterables)->exportArray());
        self::assertSame($items, $arrayProxy->exportArray());
    }

    public static function uniqueProvider(): \Generator
    {
        $defaultItem = "\x0\x0\x0";
        $items = ['100', '1e2', "\u{2010}", "\u{2011}"];

        foreach ([
            \SORT_REGULAR => ['100', 2 => "\u{2010}", "\u{2011}"],
            \SORT_NUMERIC => ['100', 2 => "\u{2010}"],
            \SORT_STRING => ['100', '1e2', "\u{2010}", "\u{2011}"],
        ] as $sortFlags => $expected) {
            yield [
                $defaultItem,
                $items,
                function (self $that) use ($sortFlags): int {
                    return $sortFlags;
                },
                $expected,
            ];
        }

        $errorMessage = self::getUniqueLocaleErrorMessage();
        if (null === $errorMessage) {
            $sortFlagsClosure = function (self $that): int {
                return \SORT_LOCALE_STRING;
            };
        } else {
            $sortFlagsClosure = function (self $that) use ($errorMessage): int {
                $that->markTestSkipped($errorMessage);
            };
        }

        yield [$defaultItem, $items, $sortFlagsClosure, ['100', '1e2', "\u{2010}"]];
    }

    /**
     * @dataProvider uniqueProvider
     */
    public function testUnique(string $defaultItem, array $items, \Closure $sortFlagsClosure, array $expected): void
    {
        $arrayProxy = new ArrayProxy($defaultItem, ...$items);
        self::assertSame($expected, \iterator_to_array($arrayProxy->unique($sortFlagsClosure($this))));
        self::assertSame($items, $arrayProxy->exportArray());
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
        $values = ['cd', 'xy', 'ef', 'ef'];
        self::assertSame($values, \iterator_to_array(self::instantiate(...$values)->values()));
    }

    /**
     * @expectedException \ArgumentCountError
     */
    public function testWalkTooFewArguments(): void
    {
        self::instantiate()->walk(function ($item, $offset, $foo) {
        });
    }

    public static function walkProvider(): \Generator
    {
        foreach ([
            [['cd', 'xy', 'ef', 'ef'], function ($item, $offset) {
                $item = 'ab';
            }, self::WALK_NO_USERDATA, ['cd', 'xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], function (&$item, $offset) {
                $item = \sprintf('%02d', $offset);
            }, self::WALK_NO_USERDATA, ['00', '01', '02', '03']],
            [['cd', 'xy', 'ef', 'ef'], function (&$item, $offset, string $userdata) {
                $item = \gettype($userdata)[0].$offset;
            }, 'foo', ['s0', 's1', 's2', 's3']],
            [['cd', 'xy', 'ef', 'ef'], function (&$item, $offset, string $userdata = null) {
                $item = \gettype($userdata)[0].$offset;
            }, null, ['N0', 'N1', 'N2', 'N3']],
        ] as [$items, $callback, $userdata, $expected]) {
            yield [$items, $callback, $userdata, $expected];
        }
    }

    /**
     * @dataProvider walkProvider
     *
     * @param mixed $userdata
     */
    public function testWalk(array $items, callable $callback, $userdata, array $expected): void
    {
        $arrayProxy = self::instantiate(...$items);
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

    public function testFillKeys(): void
    {
        $arrayProxy = self::instantiate()::fillKeys('cd', [1, 6, 3], null);
        self::assertSame(['cd', 'cd', 'cd', 'cd', 'cd', 'cd', 'cd'], $arrayProxy->exportArray());

        $arrayProxy = self::instantiate()::fillKeys('cd', [1, 6, 3], 'ab');
        self::assertSame(['cd', 'ab', 'cd', 'ab', 'cd', 'cd', 'ab'], $arrayProxy->exportArray());
    }

    private static function assertArrayMask(array $arrayMask, array $array): void
    {
        self::assertCount(\count($arrayMask), $array);
        foreach ($arrayMask as $index => &$value) {
            if (null === $value) {
                $value = $array[$index] ?? '';
            }
        }
        self::assertSame(\serialize($arrayMask), \serialize($array));
    }

    private static function getLocaleErrorMessage(): ?string
    {
        if (!\defined('\\SORT_LOCALE_STRING') || !\is_callable('\\strcoll')) {
            return 'This test requires \\SORT_LOCALE_STRING and a callable \\strcoll';
        }

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

        $items = ['12 ', '100', "\u{d6} ", 'PPP', 'ooo'];
        \sort($items, \SORT_LOCALE_STRING);
        if ($items !== ['100', '12 ', "\u{d6} ", 'ooo', 'PPP']) {
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

        $items = ["\u{2010}", "\u{2011}"];
        \sort($items, \SORT_LOCALE_STRING);
        if ($items !== ["\u{2010}"]) {
            return 'This test requires a locale to collate U+2010 and U+2011 together';
        }

        return null;
    }

    private static function instantiate(string ...$items): ArrayProxyInterface
    {
        return new ArrayProxy('ab', ...$items);
    }
}

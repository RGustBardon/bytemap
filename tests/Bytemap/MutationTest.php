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
final class MutationTest extends AbstractTestOfBytemap
{
    public static function insertionProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['z', 'x', 'y', 'w', 'u', 't'],
                ['zx', 'xy', 'yy', 'wy', 'ut', 'tu'],
            ] as $items) {
                foreach ([
                    [[], [], -3, [0, 0]],
                    [[], [], -2, [0]],
                    [[], [], -1, []],
                    [[], [], 0, []],
                    [[], [], 1, [0]],
                    [[1], [], -3, [0, 1]],
                    [[1], [], -2, [1]],
                    [[1], [], -1, [1]],
                    [[1], [], 0, [1]],
                    [[1], [], 1, [1]],
                    [[], [1], -3, [1, 0, 0]],
                    [[], [1], -2, [1, 0]],
                    [[], [1], -1, [1]],
                    [[], [1], 0, [1]],
                    [[], [1], 1, [0, 1]],
                    [[0, 1, 2, 3, 0, 1, 2], [4, 5], 3, [0, 1, 2, 4, 5, 3, 0, 1, 2]],
                    [[0, 1, 2, 3, null, 1, 2], [4, 5], 3, [0, 1, 2, 4, 5, 3, 0, 1, 2]],
                ] as [$sequence, $inserted, $firstItemOffset, $expected]) {
                    yield [$impl, $items, $sequence, $inserted, $firstItemOffset, $expected];
                }
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::insert
     * @dataProvider insertionProvider
     */
    public function testInsertion(
        string $impl,
        array $items,
        array $sequence,
        array $inserted,
        int $firstItemOffset,
        array $expected
    ): void {
        $expectedSequence = [];
        foreach ($expected as $key) {
            $expectedSequence[] = $items[$key];
        }
        $bytemap = self::instantiate($impl, $items[0]);
        foreach ($sequence as $index => $key) {
            if (null !== $key) {
                $bytemap[$index] = $items[$key];
            }
        }
        $generator = (function () use ($items, $inserted) {
            foreach ($inserted as $key) {
                yield $items[$key];
            }
        })();
        $bytemap->insert($generator, $firstItemOffset);
        self::assertSequence($expectedSequence, $bytemap);
    }

    public static function deletionProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['z', 'x', 'y', 'w', 'u', 't'],
                ['zx', 'xy', 'yy', 'wy', 'ut', 'tu'],
            ] as $items) {
                foreach ([
                    [[], -1, 0, []],
                    [[], 0, 0, []],
                    [[], 1, 0, []],
                    [[], -1, 1, []],
                    [[], 0, 1, []],
                    [[], 1, 1, []],
                    [[0], -1, 0, [0]],
                    [[0], -1, 1, []],
                    [[0], -1, 2, []],
                    [[0], 0, 0, [0]],
                    [[0], 0, 1, []],
                    [[0], 0, 2, []],
                    [[0], 1, 0, [0]],
                    [[0], 1, 1, [0]],
                    [[0], 1, 2, [0]],
                    [[0, 1], -2, 1, [1]],
                    [[0, 1], -2, 2, []],
                    [[0, 1], -1, 1, [0]],
                    [[0, 1], -1, 2, [0]],
                    [[0, 1], 0, 1, [1]],
                    [[0, 1], 0, 2, []],
                    [[0, 1], 1, 1, [0]],
                    [[0, 1], 1, 2, [0]],
                    [[0, 1, 2, 3, 4, 5], 0, 3, [3, 4, 5]],
                    [[0, 1, 2, 3, 4, 5], 1, 3, [0, 4, 5]],
                    [[0, 1, 2, 3, 4, 5], 2, 3, [0, 1, 5]],
                    [[0, 1, 2, 3, 4, 5], 3, 3, [0, 1, 2]],
                    [[0, 1, 2, 3, 4, 5], 4, 3, [0, 1, 2, 3]],
                    [[0, 1, 2, 3, 4, 5], 5, 3, [0, 1, 2, 3, 4]],
                    [[0, 1, 2, 3, 4, 5], -1, 3, [0, 1, 2, 3, 4]],
                    [[0, 1, 2, 3, 4, 5], -2, 3, [0, 1, 2, 3]],
                    [[0, 1, 2, 3, 4, 5], -3, 3, [0, 1, 2]],
                    [[0, 1, 2, 3, 4, 5], -4, 3, [0, 1, 5]],
                    [[0, 1, 2, 3, 4, 5], -5, 3, [0, 4, 5]],
                    [[null, 1, 2, 3, 4, 5], -5, 3, [0, 4, 5]],
                    [[0, 1, 2, 3, 4, 5], -6, 3, [3, 4, 5]],
                    [[1, 2, 1, 2, 1, 2], 2, 3, [1, 2, 2]],
                    [[1, null, 1, 2, 1, 0], 2, 3, [1, 0, 0]],
                ] as [$sequence, $firstItemOffset, $howMany, $expected]) {
                    yield [$impl, $items, $sequence, $firstItemOffset, $howMany, $expected];
                }
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::delete
     * @dataProvider deletionProvider
     */
    public function testDeletion(
        string $impl,
        array $items,
        array $sequence,
        int $firstItemOffset,
        int $howMany,
        array $expected
    ): void {
        $expectedSequence = [];
        foreach ($expected as $key) {
            $expectedSequence[] = $items[$key];
        }
        $bytemap = self::instantiate($impl, $items[0]);
        foreach ($sequence as $index => $key) {
            if (null !== $key) {
                $bytemap[$index] = $items[$key];
            }
        }
        $bytemap->delete($firstItemOffset, $howMany);
        self::assertSequence($expectedSequence, $bytemap);
    }
}

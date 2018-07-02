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
final class SearchTest extends AbstractTestOfBytemap
{
    public static function findingProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['z', 'x', 'y', 'w', 'u', 't'],
                ['zx', 'xy', 'yy', 'wy', 'ut', 'tu'],
            ] as $items) {
                foreach ([
                    [[], [], false, true, -1, []],
                    [[], [], false, true, 0, []],
                    [[], [], false, false, 1, []],
                    [[], [], true, false, 1, []],

                    [[1], [], false, true, \PHP_INT_MAX, []],
                    [[1], [], false, false, 0, []],
                    [[1], [], false, false, 1, [1]],
                    [[1], [], false, false, \PHP_INT_MAX, [1]],

                    [[1], [1], false, false, \PHP_INT_MAX, []],
                    [[1], [1], false, true, -2, [1]],
                    [[1], [1], false, true, -1, [1]],
                    [[1], [1], false, true, 0, []],
                    [[1], [1], false, true, 1, [1]],
                    [[1], [1], false, true, 2, [1]],

                    [[1], [2], false, false, \PHP_INT_MAX, [1]],
                    [[1], [2], false, true, -2, []],
                    [[1], [2], false, true, -1, []],
                    [[1], [2], false, true, 0, []],
                    [[1], [2], false, true, 1, []],
                    [[1], [2], false, true, 2, []],

                    [[1, 1], [1], false, true, -2, [1 => 1, 0 => 1]],
                    [[1, 1], [1], false, true, -1, [1 => 1]],
                    [[1, 1], [1], false, true, 0, []],
                    [[1, 1], [1], false, true, 1, [1]],
                    [[1, 1], [1], false, true, 2, [1, 1]],

                    [[1, 1], [1, 1], false, true, -2, [1 => 1, 0 => 1]],
                    [[1, 1], [1, 1], false, true, -1, [1 => 1]],
                    [[1, 1], [1, 1], false, true, 0, []],
                    [[1, 1], [1, 1], false, true, 1, [1]],
                    [[1, 1], [1, 1], false, true, 2, [1, 1]],

                    [[1, 1], [2, 1, 3], false, true, -2, [1 => 1, 0 => 1]],
                    [[1, 1], [2, 1, 3], false, true, -1, [1 => 1]],
                    [[1, 1], [2, 1, 3], false, true, 0, []],
                    [[1, 1], [2, 1, 3], false, true, 1, [1]],
                    [[1, 1], [2, 1, 3], false, true, 2, [1, 1]],

                    [[4, 1, 4, 1], [2, 1, 3], false, false, -2, [2 => 4, 0 => 4]],
                    [[4, 1, 4, 1], [2, 1, 3], false, false, -1, [2 => 4]],
                    [[4, 1, 4, 1], [2, 1, 3], false, false, 0, []],
                    [[4, 1, 4, 1], [2, 1, 3], false, false, 1, [0 => 4]],
                    [[4, 1, 4, 1], [2, 1, 3], false, false, 2, [0 => 4, 2 => 4]],

                    [[4, 1, 4, 1], [2, 1, 3], false, true, -2, [3 => 1, 1 => 1]],
                    [[4, 1, 4, 1], [2, 1, 3], false, true, -1, [3 => 1]],
                    [[4, 1, 4, 1], [2, 1, 3], false, true, 0, []],
                    [[4, 1, 4, 1], [2, 1, 3], false, true, 1, [1 => 1]],
                    [[4, 1, 4, 1], [2, 1, 3], false, true, 2, [1 => 1, 3 => 1]],

                    [[4, 1, 4, 1], [2, 1, 3], true, true, 2, [1 => 1, 3 => 1]],

                    [[4, null, 4, 1], [2, 0, 1], false, true, 2, [1 => 0, 3 => 1]],

                    [[4, null, 0, 1], null, false, false, 2, [0 => 4, 3 => 1]],
                    [[4, null, 0, 1], null, false, true, 2, [1 => 0, 2 => 0]],
                ] as [$subject, $query, $generator, $whitelist, $howMany, $expected]) {
                    yield [$impl, $items, $subject, $query, $generator, $whitelist, $howMany, $expected];
                }
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::find
     * @dataProvider findingProvider
     */
    public function testFinding(
        string $impl,
        array $items,
        array $subject,
        ?array $query,
        bool $generator,
        bool $whitelist,
        int $howMany,
        array $expected
    ): void {
        $expectedSequence = [];
        foreach ($expected as $index => $key) {
            $expectedSequence[$index] = $items[$key];
        }
        $bytemap = self::instantiate($impl, $items[0]);
        foreach ($subject as $index => $key) {
            if (null !== $key) {
                $bytemap[$index] = $items[$key];
            }
        }
        if (null !== $query) {
            $queryIndices = $query;
            $query = (function () use ($items, $queryIndices) {
                foreach ($queryIndices as $key) {
                    yield $items[$key];
                }
            })();
            if (!$generator) {
                $query = \iterator_to_array($query);
            }
        }
        self::assertSame($expectedSequence, \iterator_to_array($bytemap->find($query, $whitelist, $howMany)));
    }
}

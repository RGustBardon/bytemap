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

    public static function greppingProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            $items = ['z', 'x', 'y', 'w', 'u', 't'];
            foreach ([
                [[], '~~', false, 1, []],
                [[], '~~', true, 1, []],
                [[], '~x~', false, 1, []],
                [[], '~x~', true, 1, []],

                [[1], '~x~', false, 1, []],
                [[1], '~x~', true, 1, [1]],
                [[1], '~X~', true, 1, []],
                [[1], '~X~i', true, 1, [1]],
                [[1], '~z~', false, 1, [1]],
                [[1], '~z~', true, 1, []],

                [[1, 2, 3, 1], '~x~', false, -2, [2 => 3, 1 => 2]],
                [[1, 2, 3, 1], '~x~', false, -1, [2 => 3]],
                [[1, 2, 3, 1], '~x~', false, 0, []],
                [[1, 2, 3, 1], '~x~', false, 1, [1 => 2]],
                [[1, 2, 3, 1], '~x~', false, 2, [1 => 2, 2 => 3]],

                [[1, 2, 3, 1], '~x~', true, -2, [3 => 1, 0 => 1]],
                [[1, 2, 3, 1], '~x~', true, -1, [3 => 1]],
                [[1, 2, 3, 1], '~x~', true, 0, []],
                [[1, 2, 3, 1], '~x~', true, 1, [0 => 1]],
                [[1, 2, 3, 1], '~x~', true, 2, [0 => 1, 3 => 1]],

                [[1, 2, 3, null, 1], '~[axz]~', false, \PHP_INT_MAX, [1 => 2, 2 => 3]],
                [[1, 2, 3, null, 1], '~[axz]~', true, \PHP_INT_MAX, [0 => 1, 3 => 0, 4 => 1]],
                [[1, 2, 3, null, 1], '~[^axz]~', true, \PHP_INT_MAX, [1 => 2, 2 => 3]],

                [[1, 2, 3, null, 1], '~xy~', true, \PHP_INT_MAX, []],

                [[1, 2, 3, null, 1], '~^y|z$~', true, \PHP_INT_MAX, [1 => 2, 3 => 0]],

                [[1, 2, 3, null, 1], '~(?<=z)x~', true, \PHP_INT_MAX, []],
                [[1, 2, 3, null, 1], '~x(?=y)~', true, \PHP_INT_MAX, []],

                [[1, 2, 3, null, 1], '~~', true, \PHP_INT_MAX, [1, 2, 3, 0, 1]],
            ] as [$subject, $regex, $whitelist, $howMany, $expected]) {
                yield [$impl, $items, $subject, $regex, $whitelist, $howMany, $expected];
            }

            $items = ['zx', 'xy', 'yy', 'wy', 'wx', 'tu'];
            foreach ([
                [[], '~~', false, 1, []],
                [[], '~~', true, 1, []],
                [[], '~x~', false, 1, []],
                [[], '~x~', true, 1, []],

                [[1], '~xy~', false, 1, []],
                [[1], '~xy~', true, 1, [1]],
                [[1], '~Xy~', true, 1, []],
                [[1], '~Xy~i', true, 1, [1]],
                [[1], '~zx~', false, 1, [1]],
                [[1], '~zx~', true, 1, []],

                [[1, 2, 3, 1], '~xy~', false, -2, [2 => 3, 1 => 2]],
                [[1, 2, 3, 1], '~xy~', false, -1, [2 => 3]],
                [[1, 2, 3, 1], '~xy~', false, 0, []],
                [[1, 2, 3, 1], '~xy~', false, 1, [1 => 2]],
                [[1, 2, 3, 1], '~xy~', false, 2, [1 => 2, 2 => 3]],

                [[1, 2, 3, 1], '~xy~', true, -2, [3 => 1, 0 => 1]],
                [[1, 2, 3, 1], '~xy~', true, -1, [3 => 1]],
                [[1, 2, 3, 1], '~xy~', true, 0, []],
                [[1, 2, 3, 1], '~xy~', true, 1, [0 => 1]],
                [[1, 2, 3, 1], '~xy~', true, 2, [0 => 1, 3 => 1]],

                [[1, 2, 3, null, 1], '~[axz]~', false, \PHP_INT_MAX, [1 => 2, 2 => 3]],
                [[1, 2, 3, null, 1], '~[axz]~', true, \PHP_INT_MAX, [0 => 1, 3 => 0, 4 => 1]],
                [[1, 2, 3, null, 1], '~[^axz]~', true, \PHP_INT_MAX, [0 => 1, 1 => 2, 2 => 3, 4 => 1]],

                [[1, 2, 3, null, 1], '~xyy~', true, \PHP_INT_MAX, []],

                [[1, 2, 3, null, 1], '~^yy|xy$~', true, \PHP_INT_MAX, [0 => 1, 1 => 2, 4 => 1]],
                [[1, 2, 3, null, 1], '~y$~', true, \PHP_INT_MAX, [0 => 1, 1 => 2, 2 => 3, 4 => 1]],

                [[1, 2, 3, null, 1], '~(?<=z)x~', true, \PHP_INT_MAX, [3 => 0]],
                [[1, 2, 3, null, 1], '~(?!<x)y~', true, \PHP_INT_MAX, [0 => 1, 1 => 2, 2 => 3, 4 => 1]],
                [[1, 2, 3, null, 1], '~x(?=y)~', true, \PHP_INT_MAX, [0 => 1, 4 => 1]],
                [[1, 2, 3, 4, 1], '~w(?!y)~', true, \PHP_INT_MAX, [3 => 4]],

                [[1, 2, 3, null, 1], '~yy|zx~', true, \PHP_INT_MAX, [1 => 2, 3 => 0]],

                [[1, 2, 3, null, 1], '~~', true, \PHP_INT_MAX, [1, 2, 3, 0, 1]],
            ] as [$subject, $regex, $whitelist, $howMany, $expected]) {
                yield [$impl, $items, $subject, $regex, $whitelist, $howMany, $expected];
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::grep
     * @dataProvider greppingProvider
     */
    public function testGrepping(
        string $impl,
        array $items,
        array $subject,
        string $regex,
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
        self::assertSame($expectedSequence, \iterator_to_array($bytemap->grep($regex, $whitelist, $howMany)));
    }
}

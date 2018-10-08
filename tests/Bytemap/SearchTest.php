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
                    [[], [], false, true, -1, null, []],
                    [[], [], false, true, 0, null, []],
                    [[], [], false, false, 1, null, []],
                    [[], [], true, false, 1, null, []],

                    [[1], [], false, true, \PHP_INT_MAX, null, []],
                    [[1], [], false, false, 0, null, []],
                    [[1], [], false, false, 1, null, [1]],
                    [[1], [], false, false, \PHP_INT_MAX, null, [1]],

                    [[1], [1], false, false, \PHP_INT_MAX, null, []],
                    [[1], [1], false, true, -2, null, [1]],
                    [[1], [1], false, true, -1, null, [1]],
                    [[1], [1], false, true, 0, null, []],
                    [[1], [1], false, true, 1, null, [1]],
                    [[1], [1], false, true, 2, null, [1]],

                    [[1], [2], false, false, \PHP_INT_MAX, null, [1]],
                    [[1], [2], false, true, -2, null, []],
                    [[1], [2], false, true, -1, null, []],
                    [[1], [2], false, true, 0, null, []],
                    [[1], [2], false, true, 1, null, []],
                    [[1], [2], false, true, 2, null, []],

                    [[1, 1], [1], false, true, -2, null, [1 => 1, 0 => 1]],
                    [[1, 1], [1], false, true, -1, null, [1 => 1]],
                    [[1, 1], [1], false, true, 0, null, []],
                    [[1, 1], [1], false, true, 1, null, [1]],
                    [[1, 1], [1], false, true, 2, null, [1, 1]],

                    [[1, 1], [1, 1], false, true, -2, null, [1 => 1, 0 => 1]],
                    [[1, 1], [1, 1], false, true, -1, null, [1 => 1]],
                    [[1, 1], [1, 1], false, true, 0, null, []],
                    [[1, 1], [1, 1], false, true, 1, null, [1]],
                    [[1, 1], [1, 1], false, true, 2, null, [1, 1]],

                    [[1, 1], [2, 1, 3], false, true, -2, null, [1 => 1, 0 => 1]],
                    [[1, 1], [2, 1, 3], false, true, -1, null, [1 => 1]],
                    [[1, 1], [2, 1, 3], false, true, 0, null, []],
                    [[1, 1], [2, 1, 3], false, true, 1, null, [1]],
                    [[1, 1], [2, 1, 3], false, true, 2, null, [1, 1]],

                    [[4, 1, 4, 1], [2, 1, 3], false, false, -2, null, [2 => 4, 0 => 4]],
                    [[4, 1, 4, 1], [2, 1, 3], false, false, -1, null, [2 => 4]],
                    [[4, 1, 4, 1], [2, 1, 3], false, false, 0, null, []],
                    [[4, 1, 4, 1], [2, 1, 3], false, false, 1, null, [0 => 4]],
                    [[4, 1, 4, 1], [2, 1, 3], false, false, 2, null, [0 => 4, 2 => 4]],

                    [[4, 1, 4, 1], [2, 1, 3], false, true, -2, null, [3 => 1, 1 => 1]],
                    [[4, 1, 4, 1], [2, 1, 3], false, true, -1, null, [3 => 1]],
                    [[4, 1, 4, 1], [2, 1, 3], false, true, 0, null, []],
                    [[4, 1, 4, 1], [2, 1, 3], false, true, 1, null, [1 => 1]],
                    [[4, 1, 4, 1], [2, 1, 3], false, true, 2, null, [1 => 1, 3 => 1]],

                    [[4, 1, 4, 1], [2, 1, 3], true, true, 2, null, [1 => 1, 3 => 1]],

                    [[4, null, 4, 1], [2, 0, 1], false, true, 2, null, [1 => 0, 3 => 1]],

                    [[4, null, 0, 1], null, false, false, 2, null, [1 => 0, 2 => 0]],
                    [[4, null, 0, 1], null, false, true, 2, null, [0 => 4, 3 => 1]],

                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, null, [1, 2, 3, 4, 5, 1, 2]],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, 0, [1 => 2, 3, 4, 5, 1, 2]],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, 2, [3 => 4, 5, 1, 2]],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, -2, [6 => 2]],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, -7, [1 => 2, 3, 4, 5, 1, 2]],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, 6, []],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, 42, []],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, -42, [1, 2, 3, 4, 5, 1, 2]],

                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, null, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, 0, []],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, 2, [1 => 2, 0 => 1]],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, -2, [4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, -7, []],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, 6, [5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, 42, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                    [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, -42, []],
                ] as [$subject, $query, $generator, $whitelist, $howMany, $startAfter, $expected]) {
                    yield [$impl, $items, $subject, $query, $generator, $whitelist, $howMany, $startAfter, $expected];
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
        ?int $startAfter,
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
        self::assertSame($expectedSequence, \iterator_to_array($bytemap->find($query, $whitelist, $howMany, $startAfter)));
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
     * @covers \Bytemap\AbstractBytemap::find
     * @depends testFinding
     * @dataProvider implementationDirectionProvider
     */
    public function testFindingCloning(string $impl, bool $forward): void
    {
        $bytemap = self::instantiate($impl, "\x0");
        self::pushItems($bytemap, 'a', 'b', 'c', 'a', 'b', 'c');

        $matchCount = 0;
        foreach ($bytemap->find(['a', 'c'], true, $forward ? \PHP_INT_MAX : -\PHP_INT_MAX) as $item) {
            ++$matchCount;
            if (1 === $matchCount) {
                $bytemap[1] = 'a';
            }
        }
        self::assertSame(4, $matchCount);
    }

    /**
     * @covers \Bytemap\AbstractBytemap::grep
     * @dataProvider implementationProvider
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Empty regular expression
     */
    public function testGreppingInvalidPatterns(string $impl): void
    {
        self::instantiate($impl, "\x0")->grep(['~x~', '', '~y~'])->rewind();
    }

    public static function greppingProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            $items = ['z', 'x', 'y', 'w', 'u', 't'];
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
                yield [$impl, $items, $subject, $patterns, $whitelist, $howMany, $startAfter, $expected];
            }

            $items = ['zx', 'xy', 'yy', 'wy', 'wx', 'tu'];
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
            ] as [$subject, $patterns, $whitelist, $howMany, $startAfter, $expected]) {
                yield [$impl, $items, $subject, $patterns, $whitelist, $howMany, $startAfter, $expected];
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
        array $patterns,
        bool $whitelist,
        int $howMany,
        ?int $startAfter,
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
        self::assertSame($expectedSequence, \iterator_to_array($bytemap->grep($patterns, $whitelist, $howMany, $startAfter)));
    }

    /**
     * @covers \Bytemap\AbstractBytemap::grep
     * @depends testGrepping
     * @dataProvider implementationDirectionProvider
     */
    public function testGreppingCircularLookup(string $impl, bool $forward): void
    {
        $bytemap = self::instantiate($impl, "\x0\x0\x0");

        // The number of unique items should exceed `AbstractBytemap::GREP_MAXIMUM_LOOKUP_SIZE`.
        for ($item = 'aaa'; $item <= 'pzz'; ++$item) {  // 16 * 26 * 26 = 10816 items.
            $bytemap[] = $item;
        }
        $bytemap[0] = 'akk';
        $bytemap[] = 'akk';
        $pattern = '~(?<![c-d])(?<=[a-f])([k-p])(?=\\1)(?![m-n])~';  // [abef](kk|ll|oo|pp)
        $matchCount = 0;
        foreach ($bytemap->grep([$pattern], true, $forward ? \PHP_INT_MAX : -\PHP_INT_MAX) as $item) {
            ++$matchCount;
            if (1 === $matchCount) {
                $bytemap[1] = 'akk';
            }
        }
        self::assertSame(18, $matchCount);
    }
}

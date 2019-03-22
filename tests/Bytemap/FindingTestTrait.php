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
 */
trait FindingTestTrait
{
    abstract public static function assertSame($expected, $actual, string $message = ''): void;

    public static function seekableProvider(): \Generator
    {
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

            [[1], [0], false, false, \PHP_INT_MAX, null, [1]],
            [[1], [0], false, true, -2, null, []],
            [[1], [0], false, true, -1, null, []],
            [[1], [0], false, true, 0, null, []],
            [[1], [0], false, true, 1, null, []],
            [[1], [0], false, true, 2, null, []],

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

            [[1, 1], [1], false, true, -2, null, [1 => 1, 0 => 1]],
            [[1, 1], [1], false, true, -1, null, [1 => 1]],
            [[1, 1], [1], false, true, 0, null, []],
            [[1, 1], [1], false, true, 1, null, [1]],
            [[1, 1], [1], false, true, 2, null, [1, 1]],

            [[0, 1, 0, 1], [1], false, false, -2, null, [2 => 0, 0 => 0]],
            [[0, 1, 0, 1], [1], false, false, -1, null, [2 => 0]],
            [[0, 1, 0, 1], [1], false, false, 0, null, []],
            [[0, 1, 0, 1], [1], false, false, 1, null, [0 => 0]],
            [[0, 1, 0, 1], [1], false, false, 2, null, [0 => 0, 2 => 0]],

            [[0, 1, 0, 1], [1], false, true, -2, null, [3 => 1, 1 => 1]],
            [[0, 1, 0, 1], [1], false, true, -1, null, [3 => 1]],
            [[0, 1, 0, 1], [1], false, true, 0, null, []],
            [[0, 1, 0, 1], [1], false, true, 1, null, [1 => 1]],
            [[0, 1, 0, 1], [1], false, true, 2, null, [1 => 1, 3 => 1]],

            [[0, 1, 0, 1], [0, 1], false, true, -3, null, [3 => 1, 2 => 0, 1 => 1]],
            [[0, 1, 0, 1], [0, 1], false, true, 3, null, [0, 1, 0]],

            [[0, 1, 0, 1], [1], true, true, 2, null, [1 => 1, 3 => 1]],

            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, 7, null, [1, 1, 1, 1, 1, 1, 1]],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, 7, 0, [1 => 1, 1, 1, 1, 1, 1]],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, 7, 2, [3 => 1, 1, 1, 1]],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, 7, -2, [6 => 1]],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, 7, -7, [1 => 1, 1, 1, 1, 1, 1]],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, 7, 6, []],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, 7, 42, []],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, 7, -42, [1, 1, 1, 1, 1, 1, 1]],

            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, -7, null, [6 => 1, 5 => 1, 4 => 1, 3 => 1, 2 => 1, 1 => 1, 0 => 1]],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, -7, 0, []],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, -7, 2, [1 => 1, 0 => 1]],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, -7, -2, [4 => 1, 3 => 1, 2 => 1, 1 => 1, 0 => 1]],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, -7, -7, []],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, -7, 6, [5 => 1, 4 => 1, 3 => 1, 2 => 1, 1 => 1, 0 => 1]],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, -7, 42, [6 => 1, 5 => 1, 4 => 1, 3 => 1, 2 => 1, 1 => 1, 0 => 1]],
            [[1, 1, 1, 1, 1, 1, 1], [0], false, false, -7, -42, []],
        ] as [$subject, $query, $generator, $whitelist, $howMany, $startAfter, $expected]) {
            foreach (static::seekableInstanceProvider() as [$emptyBytemap, $elements]) {
                $uniqueElementsCount = \count(\array_unique($elements));

                if ($subject && $uniqueElementsCount <= \max($subject)) {
                    echo('Sipping '.\serialize([$emptyBytemap, $elements, $subject, $query, $generator, $whitelist, $howMany, $startAfter, $expected])), "\n";

                    continue;
                }

                if ($query && $uniqueElementsCount <= \max($query)) {
                    echo('Zipping '.\serialize([$emptyBytemap, $elements, $subject, $query, $generator, $whitelist, $howMany, $startAfter, $expected])), "\n";

                    continue;
                }

                yield [$emptyBytemap, $elements, $subject, $query, $generator, $whitelist, $howMany, $startAfter, $expected];
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::find
     * @covers \Bytemap\Bitmap::find
     * @dataProvider seekableProvider
     */
    public function testFinding(
        BytemapInterface $bytemap,
        array $elements,
        array $subject,
        ?array $query,
        bool $generator,
        bool $whitelist,
        int $howMany,
        ?int $startAfter,
        array $expected
    ): void {
        $bytemap[1] = $elements[0];
        $defaultValue = $bytemap[0];
        unset($bytemap[1]);
        unset($bytemap[0]);

        $expectedSequence = [];
        foreach ($expected as $index => $key) {
            $expectedSequence[$index] = null === $key ? $defaultValue : $elements[$key];
        }
        $endsWithNull = false;
        foreach ($subject as $index => $key) {
            $endsWithNull = null === $key;
            if (!$endsWithNull) {
                $bytemap[$index] = $elements[$key];
            }
        }
        if ($endsWithNull && isset($index)) {
            $bytemap[$index] = $defaultValue;
        }
        if (null !== $query) {
            $queryIndices = $query;
            $query = (static function () use ($elements, $queryIndices) {
                foreach ($queryIndices as $key) {
                    yield $elements[$key];
                }
            })();
            if (!$generator) {
                $query = \iterator_to_array($query);
            }
        }
        self::assertSame($expectedSequence, \iterator_to_array($bytemap->find($query, $whitelist, $howMany, $startAfter)));
    }

    public static function seekableWithDefaultFirstAndBooleanProvider(): \Generator
    {
        foreach ([true, false] as $forward) {
            foreach (static::seekableWithDefaultFirstProvider() as [$emptyBytemap, $elements]) {
                yield [$emptyBytemap, $elements, $forward];
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::find
     * @covers \Bytemap\Bitmap::find
     * @dataProvider seekableWithDefaultFirstAndBooleanProvider
     */
    public function testFindingNull(BytemapInterface $bytemap, array $elements, bool $whitelist): void
    {
        $bytemap[1] = $elements[1];
        $bytemap[3] = $elements[1];
        $defaultValue = $elements[0];

        if ($whitelist) {
            $expected = [1 => $bytemap[1], 3 => $bytemap[3]];
        } else {
            $expected = [0 => $bytemap[0], 2 => $bytemap[2]];
        }

        self::assertSame($expected, \iterator_to_array($bytemap->find(null, $whitelist)));
    }

    /**
     * @covers \Bytemap\AbstractBytemap::find
     * @covers \Bytemap\Bitmap::find
     * @dataProvider seekableWithDefaultFirstAndBooleanProvider
     */
    public function testFindingCloning(BytemapInterface $bytemap, array $elements, bool $forward): void
    {
        foreach ([0, 1, 0, 1, 0, 1] as $index) {
            $bytemap[] = $elements[$index];
        }

        $matchCount = 0;
        foreach ($bytemap->find([$elements[0]], true, $forward ? \PHP_INT_MAX : -\PHP_INT_MAX) as $element) {
            ++$matchCount;
            if (1 === $matchCount) {
                $bytemap[1] = $elements[0];
            }
        }
        self::assertSame(3, $matchCount);
    }

    abstract protected static function seekableWithDefaultFirstProvider(): \Generator;

    abstract protected static function seekableInstanceProvider(): \Generator;
}

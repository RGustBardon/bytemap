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
use Bytemap\Benchmark\DsBytemap;
use Bytemap\Benchmark\SplBytemap;
use PHPUnit\Framework\TestCase;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
abstract class AbstractTestOfBytemap extends TestCase
{
    public static function implementationProvider(): \Generator
    {
        foreach ([
            ArrayBytemap::class,
            DsBytemap::class,
            SplBytemap::class,
            Bytemap::class,
        ] as $impl) {
            yield [$impl];
        }
    }

    public static function arrayAccessProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['b', 'd', 'f'],
                ['bd', 'df', 'gg'],
            ] as $items) {
                yield [$impl, $items];
            }
        }
    }

    public static function invalidItemTypeProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $items]) {
            foreach ([
                false, true,
                0, 1, 10, 42,
                0., 1., 10., 42.,
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
            ] as $invalidItem) {
                yield [$impl, $items, $invalidItem];
            }
        }
    }

    public static function invalidLengthProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['a', ''],
                ['a', 'ab'],
                ['a', 'a '],
                ['a', ' a'],
                ['ab', ''],
                ['ab', 'a'],
                ['ab', 'abc'],
                ['ab', 'ab '],
                ['ab', ' ab'],
            ] as [$defaultItem, $invalidItem]) {
                yield [$impl, $defaultItem, $invalidItem];
            }
        }
    }

    protected static function instantiate(string $impl, ...$args): BytemapInterface
    {
        return new $impl(...$args);
    }

    protected static function instantiateWithSize(string $impl, array $items, int $size): BytemapInterface
    {
        $bytemap = self::instantiate($impl, $items[0]);
        for ($i = 0, $sizeOfSeed = \count($items); $i < $size; ++$i) {
            $bytemap[$i] = $items[$i % $sizeOfSeed];
        }

        return $bytemap;
    }

    protected static function pushItems(BytemapInterface $bytemap, ...$items): void
    {
        foreach ($items as $item) {
            $bytemap[] = $item;
        }
    }

    /**
     * Ensure that the information on the default item is preserved when cloning and serializing.
     *
     * @param bool|int|string $defaultItem
     * @param bool|int|string $newItem
     */
    protected static function assertDefaultItem($defaultItem, BytemapInterface $bytemap, $newItem): void
    {
        $indexOfDefaultItem = \count($bytemap);
        $indexOfNewItem = $indexOfDefaultItem + 1;
        $bytemap[$indexOfNewItem] = $newItem;
        self::assertSame($defaultItem, $bytemap[$indexOfDefaultItem]);
        self::assertSame($newItem, $bytemap[$indexOfNewItem]);
        unset($bytemap[$indexOfNewItem]);
        unset($bytemap[$indexOfDefaultItem]);
    }

    protected static function assertSequence(array $sequence, BytemapInterface $bytemap): void
    {
        self::assertCount(\count($sequence), $bytemap);
        $i = 0;
        foreach ($bytemap as $key => $value) {
            self::assertSame($i, $key);
            self::assertSame($sequence[$key], $value);
            ++$i;
        }
    }
}

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
    public static function implementationProvider(): array
    {
        return [[ArrayBytemap::class], [DsBytemap::class], [SplBytemap::class], [Bytemap::class]];
    }

    public static function tripleProvider(): array
    {
        return [['b', 'd', 'f'], ['bd', 'df', 'gg']];
    }

    public static function arrayAccessProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach (self::tripleProvider() as $items) {
                yield [$impl, $items];
            }
        }
    }

    protected static function instantiate(string $impl, ...$args): BytemapInterface
    {
        return new $impl(...$args);
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
        $i = 0;
        foreach ($bytemap as $key => $value) {
            self::assertSame($i, $key);
            self::assertSame($sequence[$key], $value);
            ++$i;
        }
        self::assertSame(\count($sequence), $i);
    }
}

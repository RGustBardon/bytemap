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
use Bytemap\Benchmark\DsDequeBytemap;
use Bytemap\Benchmark\DsVectorBytemap;
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
            DsDequeBytemap::class,
            DsVectorBytemap::class,
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
            ] as $elements) {
                yield [$impl, $elements];
            }
        }
    }

    protected static function instantiate(string $impl, ...$args): BytemapInterface
    {
        return new $impl(...$args);
    }

    protected static function instantiateWithSize(string $impl, array $elements, int $size): BytemapInterface
    {
        $bytemap = self::instantiate($impl, $elements[0]);
        for ($i = 0, $sizeOfSeed = \count($elements); $i < $size; ++$i) {
            $bytemap[$i] = $elements[$i % $sizeOfSeed];
        }

        return $bytemap;
    }

    protected static function pushElements(BytemapInterface $bytemap, ...$elements): void
    {
        foreach ($elements as $element) {
            $bytemap[] = $element;
        }
    }

    /**
     * Ensure that the information on the default value is preserved when cloning and serializing.
     *
     * @param bool|int|string $defaultValue
     * @param bool|int|string $newElement
     */
    protected static function assertDefaultValue($defaultValue, BytemapInterface $bytemap, $newElement): void
    {
        $indexOfDefaultValue = \count($bytemap);
        $indexOfNewElement = $indexOfDefaultValue + 1;
        $bytemap[$indexOfNewElement] = $newElement;
        self::assertSame($defaultValue, $bytemap[$indexOfDefaultValue]);
        self::assertSame($newElement, $bytemap[$indexOfNewElement]);
        unset($bytemap[$indexOfNewElement]);
        unset($bytemap[$indexOfDefaultValue]);
    }

    protected static function assertSequence(array $sequence, BytemapInterface $bytemap): void
    {
        self::assertCount(\count($sequence), $bytemap);
        $i = 0;
        foreach ($bytemap as $index => $element) {
            self::assertSame($i, $index);
            self::assertSame($sequence[$index], $element);
            ++$i;
        }
    }
}

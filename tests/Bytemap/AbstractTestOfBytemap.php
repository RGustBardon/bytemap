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
            ] as $elements) {
                yield [$impl, $elements];
            }
        }
    }

    public static function invalidElementTypeProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $elements]) {
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
            ] as $invalidElement) {
                yield [$impl, $elements, $invalidElement];
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
            ] as [$defaultElement, $invalidElement]) {
                yield [$impl, $defaultElement, $invalidElement];
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
     * Ensure that the information on the default element is preserved when cloning and serializing.
     *
     * @param bool|int|string $defaultElement
     * @param bool|int|string $newElement
     */
    protected static function assertDefaultElement($defaultElement, BytemapInterface $bytemap, $newElement): void
    {
        $indexOfDefaultElement = \count($bytemap);
        $indexOfNewElement = $indexOfDefaultElement + 1;
        $bytemap[$indexOfNewElement] = $newElement;
        self::assertSame($defaultElement, $bytemap[$indexOfDefaultElement]);
        self::assertSame($newElement, $bytemap[$indexOfNewElement]);
        unset($bytemap[$indexOfNewElement]);
        unset($bytemap[$indexOfDefaultElement]);
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

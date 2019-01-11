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
 * @covers \Bytemap\Benchmark\AbstractDsBytemap
 * @covers \Bytemap\Benchmark\ArrayBytemap
 * @covers \Bytemap\Benchmark\DsDequeBytemap
 * @covers \Bytemap\Benchmark\DsVectorBytemap
 * @covers \Bytemap\Benchmark\SplBytemap
 * @covers \Bytemap\Bytemap
 */
final class MutationTest extends AbstractTestOfBytemap
{
    use DeletionTestTrait;
    use InvalidLengthGeneratorTrait;
    use InvalidTypeGeneratorsTrait;

    // `DeletionTestTrait`
    protected static function deletionInstanceProvider(): \Generator
    {
        foreach (self::mutationProvider() as [$impl, $elements]) {
            yield [self::instantiate($impl, $elements[0]), $elements];
        }
    }
    
    // `MutationTest`
    public static function invalidElementTypeProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $elements]) {
            foreach (self::generateElementsOfInvalidType($elements[0]) as $invalidElement) {
                yield from self::generateInvalidElements($impl, $elements, $invalidElement);
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::insert
     * @dataProvider invalidElementTypeProvider
     *
     * @param mixed $invalidElement
     */
    public function testInsertionOfInvalidType(
        string $impl,
        array $elements,
        $invalidElement,
        bool $useGenerator,
        array $sequence,
        array $inserted,
        int $firstIndex
    ): void {
        $bytemap = self::instantiate($impl, $elements[0]);
        $expectedSequence = [];
        foreach ($sequence as $index => $key) {
            $bytemap[$index] = $expectedSequence[$index] = $elements[$key];
        }

        $generator = (function () use ($elements, $inserted, $invalidElement) {
            foreach ($inserted as $key) {
                yield null === $key ? $invalidElement : $elements[$key];
            }
        })();

        try {
            $bytemap->insert($useGenerator ? $generator : \iterator_to_array($generator), $firstIndex);
        } catch (\TypeError $e) {
        }
        self::assertTrue(isset($e), 'Failed asserting that exception of type "\\TypeError" is thrown.');
        self::assertSequence($expectedSequence, $bytemap);
    }

    public static function invalidLengthProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $elements]) {
            foreach (self::generateElementsOfInvalidLength(\strlen($elements[0])) as $invalidElement) {
                yield from self::generateInvalidElements($impl, \array_fill(0, 6, $elements[0]), $invalidElement);
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::insert
     * @dataProvider invalidLengthProvider
     */
    public function testInsertionOfInvalidLength(
        string $impl,
        array $elements,
        string $invalidElement,
        bool $useGenerator,
        array $sequence,
        array $inserted,
        int $firstIndex
    ): void {
        $bytemap = self::instantiate($impl, $elements[0]);
        $expectedSequence = [];
        foreach ($sequence as $index => $key) {
            $bytemap[$index] = $expectedSequence[$index] = $elements[$key];
        }
        $generator = (function () use ($elements, $inserted, $invalidElement) {
            foreach ($inserted as $key) {
                yield null === $key ? $invalidElement : $elements[$key];
            }
        })();

        try {
            $bytemap->insert($useGenerator ? $generator : \iterator_to_array($generator), $firstIndex);
        } catch (\DomainException $e) {
        }
        self::assertTrue(isset($e), 'Failed asserting that exception of type "\\DomainException" is thrown.');
        self::assertSequence($expectedSequence, $bytemap);
    }

    public static function insertionProvider(): \Generator
    {
        foreach (self::mutationProvider() as [$impl, $elements]) {
            foreach ([false, true] as $useGenerator) {
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
                ] as [$sequence, $inserted, $firstIndex, $expected]) {
                    yield [$impl, $elements, $useGenerator, $sequence, $inserted, $firstIndex, $expected];
                }
            }
        }
    }

    /**
     * @covers \Bytemap\Benchmark\AbstractDsBytemap::insert
     * @covers \Bytemap\Benchmark\ArrayBytemap::insert
     * @covers \Bytemap\Benchmark\SplBytemap::insert
     * @covers \Bytemap\Bytemap::insert
     * @dataProvider insertionProvider
     */
    public function testInsertion(
        string $impl,
        array $elements,
        bool $useGenerator,
        array $sequence,
        array $inserted,
        int $firstIndex,
        array $expected
    ): void {
        $expectedSequence = [];
        foreach ($expected as $key) {
            $expectedSequence[] = $elements[$key];
        }
        $bytemap = self::instantiate($impl, $elements[0]);
        foreach ($sequence as $index => $key) {
            if (null !== $key) {
                $bytemap[$index] = $elements[$key];
            }
        }
        $generator = (function () use ($elements, $inserted) {
            foreach ($inserted as $key) {
                yield $elements[$key];
            }
        })();
        $bytemap->insert($useGenerator ? $generator : \iterator_to_array($generator), $firstIndex);
        self::assertSequence($expectedSequence, $bytemap);
    }

    /**
     * @param mixed $invalidElement
     */
    private static function generateInvalidElements(string $impl, array $elements, $invalidElement): \Generator
    {
        foreach ([false, true] as $useGenerator) {
            foreach ([
                [[], [null]],
                [[0, 1, 2, 0, 1, 2], [null, 1, 1]],
                [[0, 1, 2, 0, 1, 2], [1, null, 1]],
                [[0, 1, 2, 0, 1, 2], [1, 1, null]],
            ] as [$sequence, $inserted]) {
                $size = \count($sequence);
                foreach (\array_unique([
                    -$size - 2,
                    -$size - 1,
                    0,
                    2,
                    $size - 1,
                    $size,
                    $size + 1,
                ]) as $firstIndex) {
                    yield [$impl, $elements, $invalidElement, $useGenerator, $sequence, $inserted, $firstIndex];
                }
            }
        }
    }

    private static function mutationProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['z', 'x', 'y', 'w', 'u', 't'],
                ['zx', 'xy', 'yy', 'wy', 'ut', 'tu'],
            ] as $elements) {
                yield [$impl, $elements];
            }
        }
    }
}

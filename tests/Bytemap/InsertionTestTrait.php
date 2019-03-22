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
trait InsertionTestTrait
{
    public static function invalidInsertedElementTypeProvider(): \Generator
    {
        foreach (static::insertionInstanceProvider() as [$emptyBytemap, $elements]) {
            foreach (self::generateElementsOfInvalidType($elements[0]) as $invalidElement) {
                yield from self::generateInvalidElements($emptyBytemap, $elements, $invalidElement);
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::insert
     * @dataProvider invalidInsertedElementTypeProvider
     *
     * @param mixed $invalidElement
     */
    public function testInsertionOfInvalidType(
        BytemapInterface $bytemap,
        array $elements,
        $invalidElement,
        bool $useGenerator,
        array $sequence,
        array $inserted,
        int $firstIndex
    ): void {
        $expectedSequence = [];
        foreach ($sequence as $index => $key) {
            $expectedSequence[$index] = $elements[$key];
        }

        $generator = (static function () use ($elements, $inserted, $invalidElement) {
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

    public static function insertionProvider(): \Generator
    {
        foreach (static::insertionInstanceProvider() as [$emptyBytemap, $elements]) {
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

                    // The bits are to be inserted at the beginning, so prepend (NX).
                    [
                        [0, 1, 2, 3, 0, 1, 2, 3],
                        [0, 1, 2, 3, 0, 1, 2, 3],
                        0,
                        [
                            0, 1, 2, 3, 0, 1, 2, 3,
                            0, 1, 2, 3, 0, 1, 2, 3,
                        ],
                    ],

                    // The bits are not to be inserted at the beginning, so splice (XNX).
                    [
                        [
                            0, 1, 2, 3, 0, 1, 2, 3,
                            0, 1, 2, 3, 0, 1, 2, 3,
                        ],
                        [0, 1, 2, 3, 0, 1, 2, 3],
                        8,
                        [
                            0, 1, 2, 3, 0, 1, 2, 3,
                            0, 1, 2, 3, 0, 1, 2, 3,
                            0, 1, 2, 3, 0, 1, 2, 3,
                        ],
                    ],
                ] as [$sequence, $inserted, $firstIndex, $expected]) {
                    $clone = clone $emptyBytemap;
                    foreach ($sequence as $index => $key) {
                        if (null !== $key) {
                            $clone[$index] = $elements[$key];
                        }
                    }
                    yield [$clone, $elements, $useGenerator, $sequence, $inserted, $firstIndex, $expected];
                }
            }
        }
    }

    /**
     * @covers \Bytemap\Benchmark\AbstractDsBytemap::insert
     * @covers \Bytemap\Benchmark\ArrayBytemap::insert
     * @covers \Bytemap\Benchmark\SplBytemap::insert
     * @covers \Bytemap\Bytemap::insert
     * @covers \Bytemap\Bitmap::insert
     * @dataProvider insertionProvider
     */
    public function testInsertion(
        BytemapInterface $bytemap,
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
        $generator = (static function () use ($elements, $inserted) {
            foreach ($inserted as $key) {
                yield $elements[$key];
            }
        })();
        $bytemap->insert($useGenerator ? $generator : \iterator_to_array($generator), $firstIndex);
        self::assertSequence($expectedSequence, $bytemap);
    }

    abstract public static function assertTrue($condition, string $message = ''): void;

    abstract protected static function assertSequence(array $sequence, BytemapInterface $bytemap): void;

    abstract protected static function generateInvalidElements(BytemapInterface $emptyBytemap, array $elements, $invalidElement): \Generator;

    abstract protected static function insertionInstanceProvider(): \Generator;
}

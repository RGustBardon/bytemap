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
trait DeletionTestTrait
{
    public static function deletionProvider(): \Generator
    {
        foreach (static::deletionInstanceProvider() as [$emptyBytemap, $elements]) {
            foreach ([
                [[], -1, 0, []],
                [[], 0, 0, []],
                [[], 1, 0, []],
                [[], -1, 1, []],
                [[], 0, 1, []],
                [[], 1, 1, []],
                [[0], -1, 0, [0]],
                [[0], -1, 1, []],
                [[0], -1, 2, []],
                [[0], 0, 0, [0]],
                [[0], 0, 1, []],
                [[0], 0, 2, []],
                [[0], 1, 0, [0]],
                [[0], 1, 1, [0]],
                [[0], 1, 2, [0]],
                [[0, 1], -2, 1, [1]],
                [[0, 1], -2, 2, []],
                [[0, 1], -1, 1, [0]],
                [[0, 1], -1, 2, [0]],
                [[0, 1], 0, 1, [1]],
                [[0, 1], 0, 2, []],
                [[0, 1], 1, 1, [0]],
                [[0, 1], 1, 2, [0]],
                [[0, 1, 2, 3, 4, 5], 0, 3, [3, 4, 5]],
                [[0, 1, 2, 3, 4, 5], 1, 1, [0, 2, 3, 4, 5]],
                [[0, 1, 2, 3, 4, 5], 1, 3, [0, 4, 5]],
                [[0, 1, 2, 3, 4, 5], 2, 3, [0, 1, 5]],
                [[0, 1, 2, 3, 4, 5], 3, 3, [0, 1, 2]],
                [[0, 1, 2, 3, 4, 5], 4, 3, [0, 1, 2, 3]],
                [[0, 1, 2, 3, 4, 5], 5, 3, [0, 1, 2, 3, 4]],
                [[0, 1, 2, 3, 4, 5], -1, 3, [0, 1, 2, 3, 4]],
                [[0, 1, 2, 3, 4, 5], -2, 3, [0, 1, 2, 3]],
                [[0, 1, 2, 3, 4, 5], -3, 3, [0, 1, 2]],
                [[0, 1, 2, 3, 4, 5], -4, 3, [0, 1, 5]],
                [[0, 1, 2, 3, 4, 5], -5, 3, [0, 4, 5]],
                [[null, 1, 2, 3, 4, 5], -5, 3, [0, 4, 5]],
                [[0, 1, 2, 3, 4, 5], -6, 3, [3, 4, 5]],
                [[0, 1, 2, 3, 4, 5], -7, 3, [2, 3, 4, 5]],
                [[1, 2, 1, 2, 1, 2], 2, 3, [1, 2, 2]],
                [[1, null, 1, 2, 1, 0], 2, 3, [1, 0, 0]],

                // `$howManyFullBytes > 0` and then `0 === $howMany`
                [
                    [
                        0, 1, 2, 3, 0, 1, 2, 3,
                        0, 1, 2, 3, 0, 1, 2, 3,
                        0,
                    ],
                    8,
                    8,
                    [
                        0, 1, 2, 3, 0, 1, 2, 3,
                        0,
                    ],
                ],

                // There are not enough bits in the assembled byte,
                // so augment it with the next source byte.
                [
                    [
                        0, 1, 2, 3, 0, 1, 2, 3,
                        0, 1, 2, 3, 0, 1, 2, 3,
                        0, 1, 2, 3, 0, 1, 2, 3,
                        0, 1, 2, 3, 0, 1, 2, 3,
                        0,
                    ],
                    8,
                    17,
                    [
                        0, 1, 2, 3, 0, 1, 2, 3,
                        1, 2, 3, 0, 1, 2, 3,
                        0,
                    ],
                ],
            ] as [$sequence, $firstIndex, $howMany, $expected]) {
                $clone = clone $emptyBytemap;
                foreach ($sequence as $index => $key) {
                    if (null !== $key) {
                        $clone[$index] = $elements[$key];
                    }
                }
                yield [$clone, $elements, $sequence, $firstIndex, $howMany, $expected];
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::delete
     * @covers \Bytemap\Bitmap::delete
     * @dataProvider deletionProvider
     */
    public function testDeletion(
        BytemapInterface $bytemap,
        array $elements,
        array $sequence,
        int $firstIndex,
        int $howMany,
        array $expected
    ): void {
        $expectedSequence = [];
        foreach ($expected as $key) {
            $expectedSequence[] = $elements[$key];
        }
        $bytemap->delete($firstIndex, $howMany);
        self::assertSequence($expectedSequence, $bytemap);
    }

    abstract protected static function deletionInstanceProvider(): \Generator;

    abstract protected static function assertSequence(array $sequence, BytemapInterface $bytemap): void;
}

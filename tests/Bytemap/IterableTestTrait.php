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
trait IterableTestTrait
{
    /**
     * @dataProvider iterableInstanceProvider
     */
    public function testIterable(iterable $iterable, array $elements): void
    {
        $sequence = [$elements[1], $elements[2], $elements[1]];

        foreach ($iterable as $element) {
            self::fail();
        }

        foreach ($sequence as $element) {
            $iterable[] = $element;
        }
        $i = 0;
        foreach ($iterable as $index => $element) {
            self::assertSame($i, $index);
            self::assertTrue(\array_key_exists($index, $sequence));
            self::assertSame($sequence[$index], $element);
            ++$i;
        }
        self::assertFalse(\array_key_exists($i, $sequence));

        $iterations = [];
        foreach ($iterable as $outerIndex => $outerElement) {
            if (1 === $outerIndex) {
                $iterable[] = $elements[2];
            }
            $innerIteration = [];
            foreach ($iterable as $innerIndex => $innerElement) {
                if (1 === $innerIndex) {
                    $iterable[2] = $elements[0];
                }
                $innerIteration[] = [$innerIndex, $innerElement];
            }
            $iterations[] = $innerIteration;
            $iterations[] = [$outerIndex, $outerElement];
        }
        self::assertSame([
            [[0, $elements[1]], [1, $elements[2]], [2, $elements[1]]],
            [0, $elements[1]],
            [[0, $elements[1]], [1, $elements[2]], [2, $elements[0]], [3, $elements[2]]],
            [1, $elements[2]],
            [[0, $elements[1]], [1, $elements[2]], [2, $elements[0]], [3, $elements[2]]],
            [2, $elements[1]],
        ], $iterations);
    }

    abstract public static function iterableInstanceProvider(): \Generator;
}

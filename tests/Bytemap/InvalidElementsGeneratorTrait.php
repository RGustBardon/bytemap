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
trait InvalidElementsGeneratorTrait
{
    /**
     * @param mixed $invalidElement
     */
    protected static function generateInvalidElements(BytemapInterface $emptyBytemap, array $elements, $invalidElement): \Generator
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
                    $clone = clone $emptyBytemap;
                    foreach ($sequence as $index => $key) {
                        $clone[$index] = $elements[$key];
                    }
                    yield [$clone, $elements, $invalidElement, $useGenerator, $sequence, $inserted, $firstIndex];
                }
            }
        }
    }
}

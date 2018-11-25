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
trait InvalidLengthGeneratorTrait
{
    public static function generateElementsOfInvalidLength(int $bytesPerElement): \Generator
    {
        for ($i = 0; $i < $bytesPerElement; ++$i) {
            yield \str_repeat('a', $i);
        }

        $validElement = \str_repeat('a', $bytesPerElement);
        foreach (['a', ' '] as $affix) {
            yield $affix.$validElement;
            yield $validElement.$affix;
        }
    }
}

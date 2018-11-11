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

namespace Bytemap\Benchmark;

/**
 * An implementation of the `BytemapInterface` using `\Ds\Vector`.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
final class DsVectorBytemap extends AbstractDsBytemap
{
    protected const UNSERIALIZED_CLASSES = ['Ds\\Vector'];

    // `AbstractBytemap`
    protected function createEmptyMap(): void
    {
        $this->map = new \Ds\Vector();
    }

    // `AbstractDsBytemap`
    protected function validateUnserializedMap(): void
    {
        if (!\is_object($this->map) || !($this->map instanceof \Ds\Vector)) {
            $reason = 'Failed to unserialize (the internal representation of a bytemap must be a Ds\\Vector, '.\gettype($this->map).' given)';

            throw new \TypeError(self::EXCEPTION_PREFIX.$reason);
        }
    }
}

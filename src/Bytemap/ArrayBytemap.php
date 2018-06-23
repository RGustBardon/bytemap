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
 * A naive implementation of the `BytemapInterface` using a built-in array.
 *
 * Rationale:
 * * easy to implement, understand, and thus develop the tests;
 * * illustration of the difference between the naive approach and the optimized one;
 * * benchmarking.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
final class ArrayBytemap implements BytemapInterface
{
    private $defaultItem;

    private $itemCount = 0;
    private $map = [];

    public function __construct($defaultItem)
    {
        $this->defaultItem = $defaultItem;
    }

    public function offsetExists($offset)
    {
        return $offset < $this->itemCount;
    }

    public function offsetGet($offset)
    {
        return $this->map[$offset] ?? $this->defaultItem;
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {  // `$bytemap[] = $item`
            $offset = $this->itemCount;
        }

        $this->map[$offset] = $value;
        if ($this->itemCount < $offset + 1) {
            $this->itemCount = $offset + 1;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->map[$offset]);
        if ($offset === $this->itemCount - 1) {
            --$this->itemCount;
        }
    }
}

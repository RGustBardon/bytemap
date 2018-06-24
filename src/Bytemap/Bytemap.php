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
 * An implementation of the `BytemapInterface` using a string.
 *
 * The internal string stores items of the same length.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
class Bytemap implements BytemapInterface
{
    private $defaultItem;

    private $bytesPerItem;

    private $bytesInTotal = 0;
    private $itemCount = 0;
    private $map = '';

    public function __construct($defaultItem)
    {
        $this->defaultItem = $defaultItem;

        $this->bytesPerItem = \strlen($defaultItem);
    }

    public function offsetExists($offset)
    {
        return $offset < $this->itemCount;
    }

    public function offsetGet($offset)
    {
        return \substr($this->map, $offset * $this->bytesPerItem, $this->bytesPerItem);
    }

    public function offsetSet($offset, $item)
    {
        if (null === $offset) {  // `$bytemap[] = $item`
            $offset = $this->itemCount;
        }

        $unassignedCount = $offset - $this->itemCount;
        if ($unassignedCount < 0) {
            // Case 1. Overwrite an existing item.
            $firstByteIndex = $offset * $this->bytesPerItem;
            for ($i = 0; $i < $this->bytesPerItem; ++$i) {
                $this->map[$firstByteIndex + $i] = $item[$i];
            }
        } elseif (0 === $unassignedCount) {
            // Case 2. Append an item right after the last one.
            $this->map .= $item;
            ++$this->itemCount;
            $this->bytesInTotal += $this->bytesPerItem;
        } else {
            // Case 3. Append to a gap after the last item. Fill the gap with default items.
            $this->map .= \str_repeat($this->defaultItem, $unassignedCount).$item;
            $this->itemCount += $unassignedCount + 1;
            $this->bytesInTotal = $this->itemCount * $this->bytesPerItem;
        }
    }

    public function offsetUnset($offset)
    {
        if ($offset < $this->itemCount) {
            if ($offset === $this->itemCount - 1) {
                $this->bytesInTotal -= $this->bytesPerItem;
                $this->map = \substr($this->map, 0, $this->bytesInTotal);
                --$this->itemCount;
            } else {
                $firstByteIndex = $offset * $this->bytesPerItem;
                for ($i = 0; $i < $this->bytesPerItem; ++$i) {
                    $this->map[$firstByteIndex + $i] = $this->defaultItem[$i];
                }
            }
        }
    }

    public function count()
    {
        return $this->itemCount;
    }
}

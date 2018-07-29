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

use Bytemap\AbstractBytemap;
use Bytemap\BytemapInterface;
use Bytemap\JsonListener\BytemapListener;
use JsonStreamingParser\Parser;

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
final class ArrayBytemap extends AbstractBytemap
{
    public function __construct($defaultItem)
    {
        parent::__construct($defaultItem);

        $this->map = [];
    }

    // `ArrayAccess`
    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {  // `$bytemap[] = $item`
            $offset = $this->itemCount;
        }

        $this->map[$offset] = $value;
        if ($this->itemCount < $offset + 1) {
            $this->itemCount = $offset + 1;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->map[$offset]);
        if ($this->itemCount - 1 === $offset) {
            --$this->itemCount;
        }
    }

    // `BytemapInterface`
    public function insert(iterable $items, int $firstItemOffset = -1): void
    {
        if (-1 === $firstItemOffset || $firstItemOffset > $this->itemCount - 1) {
            // Resize the bytemap if the positive first item offset is greater than the item count.
            if ($firstItemOffset > $this->itemCount) {
                $this[$firstItemOffset - 1] = $this->defaultItem;
            }

            // Append the items.
            $itemCount = \count($this->map);

            try {
                \array_push($this->map, ...$items);
            } catch (\ArgumentCountError $e) {
            }
            $this->itemCount += \count($this->map) - $itemCount;
        } else {
            $this->fillAndSort();

            $originalFirstItemOffset = $firstItemOffset;
            // Calculate the positive offset corresponding to the negative one.
            if ($firstItemOffset < 0) {
                $firstItemOffset += $this->itemCount;

                // Keep the offsets within the bounds.
                if ($firstItemOffset < 0) {
                    $firstItemOffset = 0;
                }
            }

            // Insert the items.
            $itemCount = \count($this->map);
            \array_splice($this->map, (int) $firstItemOffset, 0, \is_array($items) ? $items : \iterator_to_array($items));
            $insertedItemCount = \count($this->map) - $itemCount;
            $this->itemCount += $insertedItemCount;

            // Resize the bytemap if the negative first item offset is greater than the new item count.
            if (-$originalFirstItemOffset > $this->itemCount) {
                $overflow = -$originalFirstItemOffset - $this->itemCount - ($insertedItemCount > 0 ? 0 : 1);
                if ($overflow > 0) {
                    \array_splice($this->map, $insertedItemCount, 0, \array_fill(0, (int) $overflow, $this->defaultItem));
                    $this->itemCount += $overflow;
                }
            }
        }
    }

    public static function parseJsonStream($jsonStream, $defaultItem): BytemapInterface
    {
        $bytemap = new self($defaultItem);
        if (self::hasStreamingParser()) {
            $listener = new BytemapListener(static function ($value, $key) use ($bytemap) {
                if (null === $key) {
                    $bytemap[] = $value;
                } else {
                    $bytemap[$key] = $value;
                }
            });
            (new Parser($jsonStream, $listener))->parse();
        } else {
            $bytemap->map = \json_decode(\stream_get_contents($jsonStream), true);
            $bytemap->deriveProperties();
        }

        return $bytemap;
    }

    // `AbstractBytemap`
    protected function createEmptyMap(): void
    {
        $this->map = [];
    }

    protected function deleteWithPositiveOffset(int $firstItemOffset, int $howMany, int $itemCount): void
    {
        $maximumRange = $itemCount - $firstItemOffset;
        if ($howMany >= $maximumRange) {
            $this->itemCount -= $maximumRange;
            while (--$maximumRange >= 0) {
                unset($this->map[--$itemCount]);
            }
        } else {
            $this->fillAndSort();
            \array_splice($this->map, $firstItemOffset, $howMany);
            $this->map = \array_diff($this->map, [$this->defaultItem]);
            $this->itemCount -= $howMany;
            if (!isset($this->map[$this->itemCount - 1])) {
                $this->map[$this->itemCount - 1] = $this->defaultItem;
            }
        }
    }

    protected function deriveProperties(): void
    {
        $this->itemCount = self::getMaxKey($this->map) + 1;
    }

    protected function fillAndSort(): void
    {
        if (\count($this->map) !== $this->itemCount) {
            $this->map += \array_fill(0, $this->itemCount, $this->defaultItem);
        }
        \ksort($this->map, \SORT_NUMERIC);
    }
}

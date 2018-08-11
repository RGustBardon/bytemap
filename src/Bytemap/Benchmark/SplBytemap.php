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
 * An implementation of the `BytemapInterface` using `\SplFixedArray`.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
class SplBytemap extends AbstractBytemap
{
    protected const UNSERIALIZED_CLASSES = ['SplFixedArray'];

    public function __clone()
    {
        $this->map = clone $this->map;
    }

    // `ArrayAccess`
    public function offsetSet($offset, $item): void
    {
        if (null === $offset) {  // `$bytemap[] = $item`
            $offset = $this->itemCount;
        }

        $unassignedCount = (int) $offset - $this->itemCount;
        if ($unassignedCount >= 0) {
            $this->map->setSize($this->itemCount + $unassignedCount + 1);
            $this->itemCount += $unassignedCount + 1;
        }
        $this->map[$offset] = $item;
    }

    public function offsetUnset($offset): void
    {
        if ($this->itemCount > $offset) {
            unset($this->map[$offset]);
            if ($this->itemCount - 1 === $offset) {
                --$this->itemCount;
            }
        }
    }

    // `Serializable`
    public function unserialize($serialized)
    {
        [$this->defaultItem, $this->map] =
            \unserialize($serialized, ['allowed_classes' => self::UNSERIALIZED_CLASSES]);
        $this->map->__wakeup();
        $this->deriveProperties();
    }

    // `BytemapInterface`
    public function insert(iterable $items, int $firstItemOffset = -1): void
    {
        $originalFirstItemOffset = $firstItemOffset;

        // Resize the bytemap if the positive first item offset is greater than the item count.
        if ($firstItemOffset > $this->itemCount) {
            $this[$firstItemOffset - 1] = $this->defaultItem;
        }

        // Allocate the memory.
        $newSize = $this->calculateNewSize($items, $firstItemOffset);
        if (null !== $newSize) {
            $this->map->setSize($newSize);
        }

        // Calculate the positive offset corresponding to the negative one.
        if ($firstItemOffset < 0) {
            $firstItemOffset += $this->itemCount;

            // Keep the offsets within the bounds.
            if ($firstItemOffset < 0) {
                $firstItemOffset = 0;
            }
        }

        // Append the items.
        $originalItemCount = $this->itemCount;
        if (isset($newSize)) {
            $itemCount = $originalItemCount;
            foreach ($items as $item) {
                $this->map[$itemCount] = $item;
                ++$itemCount;
            }
            $this->itemCount = $itemCount;
        } else {
            foreach ($items as $item) {
                $this[] = $item;
            }
        }

        // Resize the bytemap if the negative first item offset is greater than the new item count.
        if (-$originalFirstItemOffset > $this->itemCount) {
            $lastItemOffset = -$originalFirstItemOffset - ($this->itemCount > $originalItemCount ? 1 : 2);
            if ($lastItemOffset >= $this->itemCount) {
                $this[$lastItemOffset] = $this->defaultItem;
            }
        }

        // The juggling algorithm.
        $n = $this->itemCount - $firstItemOffset;
        $shift = $n - $this->itemCount + $originalItemCount;
        $gcd = self::calculateGreatestCommonDivisor($n, $shift);

        for ($i = 0; $i < $gcd; ++$i) {
            $tmp = $this->map[$firstItemOffset + $i];
            $j = $i;
            while (true) {
                $k = $j + $shift;
                if ($k >= $n) {
                    $k -= $n;
                }
                if ($k === $i) {
                    break;
                }
                $this->map[$firstItemOffset + $j] = $this->map[$firstItemOffset + $k];
                $j = $k;
            }
            $this->map[$firstItemOffset + $j] = $tmp;
        }
    }

    public static function parseJsonStream($jsonStream, $defaultItem): BytemapInterface
    {
        $bytemap = new self($defaultItem);
        if (self::hasStreamingParser()) {
            $maxKey = -1;
            $listener = new BytemapListener(function ($value, $key) use ($bytemap, &$maxKey) {
                if (null === $key) {
                    $bytemap[] = $value;
                } else {
                    if ($key > $maxKey) {
                        $maxKey = $key;
                        $bytemap->map->setSize($maxKey + 1);
                    }
                    $bytemap[$key] = $value;
                }
            });
            (new Parser($jsonStream, $listener))->parse();
        } else {
            $map = \json_decode(\stream_get_contents($jsonStream), true);
            if ($map) {
                $bytemap->map = \SplFixedArray::fromArray($map);
                $bytemap->deriveProperties();
            }
        }

        return $bytemap;
    }

    // `AbstractBytemap`
    protected function createEmptyMap(): void
    {
        $this->map = new \SplFixedArray(0);
    }

    protected function deleteWithPositiveOffset(int $firstItemOffset, int $howMany, int $itemCount): void
    {
        // Keep the offsets within the bounds.
        $howMany = \min($howMany, $itemCount - $firstItemOffset);

        // Shift all the subsequent items left by the numbers of items deleted.
        for ($i = $firstItemOffset + $howMany; $i < $itemCount; ++$i) {
            $this->map[$i - $howMany] = $this->map[$i];
        }

        // Delete the trailing items.
        $this->itemCount -= $howMany;
        while ($howMany > 0) {
            unset($this->map[--$itemCount]);
            --$howMany;
        }
    }

    protected function deriveProperties(): void
    {
        $this->itemCount = \count($this->map);
    }

    protected function findArrayItems(
        array $items,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        $defaultItem = $this->defaultItem;
        $map = clone $this->map;
        if ($howManyToReturn > 0) {
            if ($whitelist) {
                for ($i = $howManyToSkip, $itemCount = $this->itemCount; $i < $itemCount; ++$i) {
                    if (isset($items[$item = $map[$i] ?? $defaultItem])) {
                        yield $i => $item;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            } else {
                for ($i = $howManyToSkip, $itemCount = $this->itemCount; $i < $itemCount; ++$i) {
                    if (!isset($items[$item = $map[$i] ?? $defaultItem])) {
                        yield $i => $item;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            }
        } elseif ($whitelist) {
            for ($i = $this->itemCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (isset($items[$item = $map[$i] ?? $defaultItem])) {
                    yield $i => $item;
                    if (0 === ++$howManyToReturn) {
                        return;
                    }
                }
            }
        } else {
            for ($i = $this->itemCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (!isset($items[$item = $map[$i] ?? $defaultItem])) {
                    yield $i => $item;
                    if (0 === ++$howManyToReturn) {
                        return;
                    }
                }
            }
        }
    }

    protected function grepMultibyte(
        string $regex,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        $lookup = [];
        $lookupSize = 0;
        $match = null;

        $defaultItem = $this->defaultItem;
        $map = clone $this->map;
        if ($howManyToReturn > 0) {
            for ($i = $howManyToSkip, $itemCount = $this->itemCount; $i < $itemCount; ++$i) {
                if (!($whitelist xor $lookup[$item = $map[$i] ?? $defaultItem] ?? ($match = \preg_match($regex, $item)))) {
                    yield $i => $item;
                    if (0 === --$howManyToReturn) {
                        return;
                    }
                }
                if (null !== $match) {
                    $lookup[$item] = $match;
                    $match = null;
                    if ($lookupSize > self::GREP_MAXIMUM_LOOKUP_SIZE) {
                        unset($lookup[\key($lookup)]);
                    } else {
                        ++$lookupSize;
                    }
                }
            }
        } else {
            for ($i = $this->itemCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (!($whitelist xor $lookup[$item = $map[$i] ?? $defaultItem] ?? ($match = \preg_match($regex, $item)))) {
                    yield $i => $item;
                    if (0 === ++$howManyToReturn) {
                        return;
                    }
                }
                if (null !== $match) {
                    $lookup[$item] = $match;
                    $match = null;
                    if ($lookupSize > self::GREP_MAXIMUM_LOOKUP_SIZE) {
                        unset($lookup[\key($lookup)]);
                    } else {
                        ++$lookupSize;
                    }
                }
            }
        }
    }
}

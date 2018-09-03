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

        if (\is_int($offset) && $offset >= 0 && \is_string($item) && \strlen($item) === $this->bytesPerItem) {
            $unassignedCount = $offset - $this->itemCount;
            if ($unassignedCount >= 0) {
                $this->map->setSize($this->itemCount + $unassignedCount + 1);
                $this->itemCount += $unassignedCount + 1;
            }
            $this->map[$offset] = $item;
        } else {
            self::throwOnOffsetSet($offset, $item);
        }
    }

    public function offsetUnset($offset): void
    {
        if (\is_int($offset) && $offset >= 0 && $offset < $this->itemCount) {
            unset($this->map[$offset]);
            if ($this->itemCount - 1 === $offset) {
                --$this->itemCount;
            }
        }
    }

    // `Serializable`
    public function unserialize($serialized)
    {
        $this->unserializeAndValidate($serialized);
        $this->map->__wakeup();
        $this->validateUnserializedItems();
        $this->deriveProperties();
    }

    // `BytemapInterface`
    public function insert(iterable $items, int $firstItemOffset = -1): void
    {
        $originalFirstItemOffset = $firstItemOffset;
        $itemCountBeforeResizing = $this->itemCount;

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
            $bytesPerItem = $this->bytesPerItem;
            $itemCount = $originalItemCount;
            foreach ($items as $item) {
                if (\is_string($item) && \strlen($item) === $bytesPerItem) {
                    $this->map[$itemCount] = $item;
                    ++$itemCount;
                } else {
                    $this->itemCount = $itemCount;
                    $this->delete($itemCountBeforeResizing);
                    if (\is_string($item)) {
                        throw new \LengthException(self::EXCEPTION_PREFIX.'Value must be exactly '.$bytesPerItem.' bytes, '.\strlen($item).' given');
                    }

                    throw new \TypeError(self::EXCEPTION_PREFIX.'Value must be of type string, '.\gettype($item).' given');
                }
            }
            $this->itemCount = $itemCount;
        } else {
            try {
                foreach ($items as $item) {
                    $this[] = $item;
                }
            } catch (\TypeError | \LengthException $e) {
                $this->delete($itemCountBeforeResizing);

                throw $e;
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
        self::ensureStream($jsonStream);

        $bytemap = new self($defaultItem);
        if (self::hasStreamingParser()) {
            $maxKey = -1;
            $listener = new BytemapListener(function (string $value, ?int $key) use ($bytemap, &$maxKey) {
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
            self::parseJsonStreamOnline($jsonStream, $listener);
        } else {
            $map = \json_decode(\stream_get_contents($jsonStream), true);
            self::ensureJsonDecodedSuccessfully($defaultItem, $map);
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

    protected function deleteWithNonNegativeOffset(int $firstItemOffset, int $howMany, int $itemCount): void
    {
        // Keep the offsets within the bounds.
        $howMany = \min($howMany, $itemCount - $firstItemOffset);

        // Shift all the subsequent items left by the numbers of items deleted.
        for ($i = $firstItemOffset + $howMany; $i < $itemCount; ++$i) {
            $this->map[$i - $howMany] = $this->map[$i];
        }

        // Delete the trailing items.
        $this->itemCount -= $howMany;
        if (0 === $this->itemCount) {
            $this->createEmptyMap();
        } else {
            $this->map->setSize($this->itemCount);
        }
    }

    protected function deriveProperties(): void
    {
        parent::deriveProperties();

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

    protected function validateUnserializedItems(): void
    {
        if (!\is_object($this->map)) {
            $reason = 'Failed to unserialize (the internal representation of a bytemap must be an SplFixedArray, '.\gettype($this->map).' given)';

            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.$reason);
        }

        if (!($this->map instanceof \SplFixedArray)) {
            $reason = 'Failed to unserialize (the internal representation of a bytemap must be an SplFixedArray, '.\get_class($this->map).' given)';

            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.$reason);
        }

        $bytesPerItem = \strlen($this->defaultItem);
        foreach ($this->map as $item) {
            if (null !== $item) {
                if (!\is_string($item)) {
                    throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be of type string, '.\gettype($item).' given)');
                }
                if (\strlen($item) !== $bytesPerItem) {
                    throw new \LengthException(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be exactly '.$bytesPerItem.' bytes, '.\strlen($item).' given)');
                }
            }
        }
    }
}

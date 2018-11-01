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
 *
 * @internal
 */
final class SplBytemap extends AbstractBytemap
{
    protected const UNSERIALIZED_CLASSES = ['SplFixedArray'];

    public function __clone()
    {
        $this->map = clone $this->map;
    }

    // `ArrayAccess`
    public function offsetGet($index): string
    {
        if (\is_int($index) && $index >= 0 && $index < $this->itemCount) {
            return $this->map[$index] ?? $this->defaultItem;
        }

        self::throwOnOffsetGet($index);
    }

    public function offsetSet($index, $item): void
    {
        if (null === $index) {  // `$bytemap[] = $item`
            $index = $this->itemCount;
        }

        if (\is_int($index) && $index >= 0 && \is_string($item) && \strlen($item) === $this->bytesPerItem) {
            $unassignedCount = $index - $this->itemCount;
            if ($unassignedCount >= 0) {
                $this->map->setSize($this->itemCount + $unassignedCount + 1);
                $this->itemCount += $unassignedCount + 1;
            }
            $this->map[$index] = $item;
        } else {
            self::throwOnOffsetSet($index, $item, $this->bytesPerItem);
        }
    }

    public function offsetUnset($index): void
    {
        $itemCount = $this->itemCount;
        if (\is_int($index) && $index >= 0 && $index < $itemCount) {
            // Shift all the subsequent items left by one.
            for ($i = $index + 1; $i < $itemCount; ++$i) {
                $this->map[$i - 1] = $this->map[$i];
            }

            $this->map->setSize(--$this->itemCount);
        }
    }

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        return (static function (self $bytemap): \Generator {
            for ($i = 0, $defaultItem = $bytemap->defaultItem, $itemCount = $bytemap->itemCount; $i < $itemCount; ++$i) {
                yield $i => $bytemap->map[$i] ?? $defaultItem;
            }
        })(clone $this);
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        $completeMap = [];
        for ($i = 0, $defaultItem = $this->defaultItem, $itemCount = $this->itemCount; $i < $itemCount; ++$i) {
            $completeMap[$i] = $this->map[$i] ?? $defaultItem;
        }

        return $completeMap;
    }

    // `Serializable`
    public function unserialize($serialized)
    {
        $this->unserializeAndValidate($serialized);
        if (\is_object($this->map) && $this->map instanceof \SplFixedArray) {
            $this->map->__wakeup();
        }
        $this->validateUnserializedItems();
        $this->deriveProperties();
    }

    // `BytemapInterface`
    public function insert(iterable $items, int $firstItemIndex = -1): void
    {
        $originalFirstItemIndex = $firstItemIndex;
        $itemCountBeforeResizing = $this->itemCount;

        // Resize the bytemap if the positive first item index is greater than the item count.
        if ($firstItemIndex > $this->itemCount) {
            $this[$firstItemIndex - 1] = $this->defaultItem;
        }

        // Allocate the memory.
        $newSize = $this->calculateNewSize($items, $firstItemIndex);
        if (null !== $newSize) {
            $this->map->setSize($newSize);
        }

        // Calculate the positive index corresponding to the negative one.
        if ($firstItemIndex < 0) {
            $firstItemIndex += $this->itemCount;

            // Keep the indices within the bounds.
            if ($firstItemIndex < 0) {
                $firstItemIndex = 0;
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
                        throw new \DomainException(self::EXCEPTION_PREFIX.'Value must be exactly '.$bytesPerItem.' bytes, '.\strlen($item).' given');
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
            } catch (\TypeError | \DomainException $e) {
                $this->delete($itemCountBeforeResizing);

                throw $e;
            }
        }

        // Resize the bytemap if the negative first item index is greater than the new item count.
        if (-$originalFirstItemIndex > $this->itemCount) {
            $lastItemIndex = -$originalFirstItemIndex - ($this->itemCount > $originalItemCount ? 1 : 2);
            if ($lastItemIndex >= $this->itemCount) {
                $this[$lastItemIndex] = $this->defaultItem;
            }
        }

        // The juggling algorithm.
        $n = $this->itemCount - $firstItemIndex;
        $shift = $n - $this->itemCount + $originalItemCount;
        $gcd = self::calculateGreatestCommonDivisor($n, $shift);

        for ($i = 0; $i < $gcd; ++$i) {
            $tmp = $this->map[$firstItemIndex + $i];
            $j = $i;
            while (true) {
                $k = $j + $shift;
                if ($k >= $n) {
                    $k -= $n;
                }
                if ($k === $i) {
                    break;
                }
                $this->map[$firstItemIndex + $j] = $this->map[$firstItemIndex + $k];
                $j = $k;
            }
            $this->map[$firstItemIndex + $j] = $tmp;
        }
    }

    public function streamJson($stream): void
    {
        self::ensureStream($stream);

        $defaultItem = $this->defaultItem;

        $buffer = '[';
        for ($i = 0, $penultimate = $this->itemCount - 1; $i < $penultimate; ++$i) {
            $buffer .= \json_encode($this->map[$i] ?? $defaultItem).',';
            if (\strlen($buffer) > self::STREAM_BUFFER_SIZE) {
                self::stream($stream, $buffer);
                $buffer = '';
            }
        }
        $buffer .= ($this->itemCount > 0 ? \json_encode($this->map[$i] ?? $defaultItem) : '').']';
        self::stream($stream, $buffer);
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
            $map = self::parseJsonStreamNatively($jsonStream);
            self::validateMapAndGetMaxKey($map, $defaultItem);
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

    protected function deleteWithNonNegativeIndex(int $firstItemIndex, int $howMany, int $itemCount): void
    {
        // Keep the indices within the bounds.
        $howMany = \min($howMany, $itemCount - $firstItemIndex);

        // Shift all the subsequent items left by the number of items deleted.
        for ($i = $firstItemIndex + $howMany; $i < $itemCount; ++$i) {
            $this->map[$i - $howMany] = $this->map[$i];
        }

        // Delete the trailing items.
        $this->itemCount -= $howMany;
        $this->map->setSize($this->itemCount);
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
        array $patterns,
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
                if (!($whitelist xor $lookup[$item = $map[$i] ?? $defaultItem] ?? ($match = (null !== \preg_filter($patterns, '', $item))))) {
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
                if (!($whitelist xor $lookup[$item = $map[$i] ?? $defaultItem] ?? ($match = (null !== \preg_filter($patterns, '', $item))))) {
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
        if (!\is_object($this->map) || !($this->map instanceof \SplFixedArray)) {
            $reason = 'Failed to unserialize (the internal representation of a bytemap must be an SplFixedArray, '.\gettype($this->map).' given)';

            throw new \TypeError(self::EXCEPTION_PREFIX.$reason);
        }

        $bytesPerItem = \strlen($this->defaultItem);
        foreach ($this->map as $item) {
            if (null !== $item) {
                if (!\is_string($item)) {
                    throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be of type string, '.\gettype($item).' given)');
                }
                if (\strlen($item) !== $bytesPerItem) {
                    throw new \DomainException(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be exactly '.$bytesPerItem.' bytes, '.\strlen($item).' given)');
                }
            }
        }
    }
}

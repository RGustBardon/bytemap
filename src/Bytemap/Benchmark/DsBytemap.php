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
 * An implementation of the `BytemapInterface` using `\Ds\Vector`.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
final class DsBytemap extends AbstractBytemap
{
    protected const UNSERIALIZED_CLASSES = ['Ds\\Vector'];

    public function __clone()
    {
        $this->map = clone $this->map;
    }

    // `ArrayAccess`
    public function offsetGet($index): string
    {
        if (\is_int($index) && $index >= 0 && $index < $this->itemCount) {
            return $this->map[$index];
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
            if ($unassignedCount < 0) {
                // Case 1. Overwrite an existing item.
                $this->map[$index] = $item;
            } elseif (0 === $unassignedCount) {
                // Case 2. Append an item right after the last one.
                $this->map[] = $item;
                ++$this->itemCount;
            } else {
                // Case 3. Append to a gap after the last item. Fill the gap with default items.
                $this->map->allocate($this->itemCount + $unassignedCount + 1);
                for ($i = 0; $i < $unassignedCount; ++$i) {
                    $this->map[] = $this->defaultItem;
                }
                $this->map[] = $item;
                $this->itemCount += $unassignedCount + 1;
            }
        } else {
            self::throwOnOffsetSet($index, $item, $this->bytesPerItem);
        }
    }

    public function offsetUnset($index): void
    {
        if (\is_int($index) && $index >= 0 && $index < $this->itemCount) {
            --$this->itemCount;
            unset($this->map[$index]);
        }
    }

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        return clone $this->map;
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        return $this->map->toArray();
    }

    // `BytemapInterface`
    public function insert(iterable $items, int $firstIndex = -1): void
    {
        $originalItemCount = $this->itemCount;

        // Resize the bytemap if the positive first item index is greater than the item count.
        if ($firstIndex > $this->itemCount) {
            $this[$firstIndex - 1] = $this->defaultItem;
        }

        // Allocate the memory.
        $newSize = $this->calculateNewSize($items, $firstIndex);
        if (null !== $newSize) {
            $this->map->allocate($newSize);
        }

        if (-1 === $firstIndex || $firstIndex > $this->itemCount - 1) {
            $firstIndexToCheck = $this->itemCount;

            // Append the items.
            $this->map->push(...$items);

            $this->itemCount = \count($this->map);
            $this->validateInsertedItems($firstIndexToCheck, $this->itemCount - $firstIndexToCheck, $originalItemCount, $this->itemCount - $originalItemCount);
        } else {
            $originalFirstIndex = $firstIndex;
            // Calculate the positive index corresponding to the negative one.
            if ($firstIndex < 0) {
                $firstIndex += $this->itemCount;

                // Keep the indices within the bounds.
                if ($firstIndex < 0) {
                    $firstIndex = 0;
                }
            }

            // Insert the items.
            $itemCount = \count($this->map);
            $this->map->insert($firstIndex, ...$items);
            $insertedItemCount = \count($this->map) - $itemCount;
            $this->itemCount += $insertedItemCount;
            $this->validateInsertedItems($firstIndex, $insertedItemCount, $firstIndex, $insertedItemCount);

            // Resize the bytemap if the negative first item index is greater than the new item count.
            if (-$originalFirstIndex > $this->itemCount) {
                $overflow = -$originalFirstIndex - $this->itemCount - ($insertedItemCount > 0 ? 0 : 1);
                if ($overflow > 0) {
                    $this->map->insert($insertedItemCount, ...(function () use ($overflow): \Generator {
                        do {
                            yield $this->defaultItem;
                        } while (--$overflow > 0);
                    })());
                }
            }
            $this->itemCount = \count($this->map);
        }
    }

    public function streamJson($stream): void
    {
        self::ensureStream($stream);

        $defaultItem = $this->defaultItem;

        $buffer = '[';
        for ($i = 0, $penultimate = $this->itemCount - 1; $i < $penultimate; ++$i) {
            $buffer .= \json_encode($this->map[$i]).',';
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
                    $unassignedCount = $key - $maxKey - 1;
                    if ($unassignedCount < 0) {
                        $bytemap[$key] = $value;
                    } else {
                        if ($unassignedCount > 0) {
                            $bytemap->map->allocate($maxKey + 1);
                            for ($i = 0; $i < $unassignedCount; ++$i) {
                                $bytemap[] = $bytemap->defaultItem;
                            }
                        }
                        $bytemap[] = $value;
                        $maxKey = $key;
                    }
                }
            });
            self::parseJsonStreamOnline($jsonStream, $listener);
        } else {
            $map = self::parseJsonStreamNatively($jsonStream);
            [$maxKey, $sorted] = self::validateMapAndGetMaxKey($map, $defaultItem);
            $size = \count($map);
            if ($size > 0) {
                $bytemap->map->allocate($maxKey + 1);
                if (!$sorted) {
                    \ksort($map, \SORT_NUMERIC);
                }
                if ($maxKey + 1 === $size) {
                    $bytemap->map->push(...$map);
                } else {
                    $lastIndex = -1;
                    foreach ($map as $key => $value) {
                        for ($i = $lastIndex + 1; $i < $key; ++$i) {
                            $bytemap[$i] = $defaultItem;
                        }
                        $bytemap[$lastIndex = $key] = $value;
                    }
                }
                $bytemap->deriveProperties();
            }
        }

        return $bytemap;
    }

    // `AbstractBytemap`
    protected function createEmptyMap(): void
    {
        $this->map = new \Ds\Vector();
    }

    protected function deleteWithNonNegativeIndex(int $firstIndex, int $howMany, int $itemCount): void
    {
        $maximumRange = $itemCount - $firstIndex;
        if ($howMany >= $maximumRange) {
            $this->itemCount -= $maximumRange;
            while (--$maximumRange >= 0) {
                $this->map->pop();
            }
        } else {
            $this->itemCount -= $howMany;
            $this->map = $this->map->slice(0, $firstIndex)->merge($this->map->slice($firstIndex + $howMany));
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
        $map = clone $this->map;
        if ($howManyToReturn > 0) {
            if ($whitelist) {
                for ($i = $howManyToSkip, $itemCount = $this->itemCount; $i < $itemCount; ++$i) {
                    if (isset($items[$item = $map[$i]])) {
                        yield $i => $item;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            } else {
                for ($i = $howManyToSkip, $itemCount = $this->itemCount; $i < $itemCount; ++$i) {
                    if (!isset($items[$item = $map[$i]])) {
                        yield $i => $item;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            }
        } elseif ($whitelist) {
            for ($i = $this->itemCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (isset($items[$item = $map[$i]])) {
                    yield $i => $item;
                    if (0 === ++$howManyToReturn) {
                        return;
                    }
                }
            }
        } else {
            for ($i = $this->itemCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (!isset($items[$item = $map[$i]])) {
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

        $clone = clone $this;
        if ($howManyToReturn > 0) {
            for ($i = $howManyToSkip, $itemCount = $this->itemCount; $i < $itemCount; ++$i) {
                if (!($whitelist xor $lookup[$item = $clone->map[$i]] ?? ($match = (null !== \preg_filter($patterns, '', $item))))) {
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
                if (!($whitelist xor $lookup[$item = $clone->map[$i]] ?? ($match = (null !== \preg_filter($patterns, '', $item))))) {
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
        if (!\is_object($this->map) || !($this->map instanceof \Ds\Vector)) {
            $reason = 'Failed to unserialize (the internal representation of a bytemap must be a Ds\\Vector, '.\gettype($this->map).' given)';

            throw new \TypeError(self::EXCEPTION_PREFIX.$reason);
        }

        $bytesPerItem = \strlen($this->defaultItem);
        foreach ($this->map as $item) {
            if (!\is_string($item)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be of type string, '.\gettype($item).' given)');
            }
            if (\strlen($item) !== $bytesPerItem) {
                throw new \DomainException(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be exactly '.$bytesPerItem.' bytes, '.\strlen($item).' given)');
            }
        }
    }

    // `DsBytemap`
    protected function validateInsertedItems(
        int $firstIndexToCheck,
        int $howManyToCheck,
        int $firstIndexToRollBack,
        int $howManyToRollBack
        ): void {
        $bytesPerItem = $this->bytesPerItem;
        $lastItemIndexToCheck = $firstIndexToCheck + $howManyToCheck;
        for ($index = $firstIndexToCheck; $index < $lastItemIndexToCheck; ++$index) {
            if (!\is_string($item = $this->map[$index])) {
                $this->delete($firstIndexToRollBack, $howManyToRollBack);

                throw new \TypeError(self::EXCEPTION_PREFIX.'Value must be of type string, '.\gettype($item).' given');
            }
            if (\strlen($item) !== $bytesPerItem) {
                $this->delete($firstIndexToRollBack, $howManyToRollBack);

                throw new \DomainException(self::EXCEPTION_PREFIX.'Value must be exactly '.$bytesPerItem.' bytes, '.\strlen($item).' given');
            }
        }
    }
}

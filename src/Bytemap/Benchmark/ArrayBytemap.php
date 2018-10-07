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
    // `ArrayAccess`
    public function offsetGet($offset): string
    {
        if (\is_int($offset) && $offset >= 0 && $offset < $this->itemCount) {
            return $this->map[$offset] ?? $this->defaultItem;
        }

        self::throwOnOffsetGet($offset);
    }

    public function offsetSet($offset, $item): void
    {
        if (null === $offset) {  // `$bytemap[] = $item`
            $offset = $this->itemCount;
        }

        if (\is_int($offset) && $offset >= 0 && \is_string($item) && \strlen($item) === $this->bytesPerItem) {
            $this->map[$offset] = $item;
            if ($this->itemCount < $offset + 1) {
                $this->itemCount = $offset + 1;
            }
        } else {
            self::throwOnOffsetSet($offset, $item, $this->bytesPerItem);
        }
    }

    public function offsetUnset($offset): void
    {
        if (\is_int($offset) && $offset >= 0 && $offset < $this->itemCount) {
            if ($this->itemCount - 1 === $offset) {
                --$this->itemCount;
                unset($this->map[$offset]);
            } else {
                $this->fillAndSort();
                \array_splice($this->map, $offset, 1);
                $this->map = \array_diff($this->map, [$this->defaultItem]);
                --$this->itemCount;
                if (!isset($this->map[$this->itemCount - 1])) {
                    $this->map[$this->itemCount - 1] = $this->defaultItem;
                }
            }
        }
    }

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        return (static function (self $bytemap): \Generator {
            for ($i = 0, $itemCount = $bytemap->itemCount, $defaultItem = $bytemap->defaultItem; $i < $itemCount; ++$i) {
                yield $i => $bytemap->map[$i] ?? $defaultItem;
            }
        })(clone $this);
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        $completeMap = [];
        for ($i = 0, $itemCount = $this->itemCount, $defaultItem = $this->defaultItem; $i < $itemCount; ++$i) {
            $completeMap[$i] = $this->map[$i] ?? $defaultItem;
        }

        return $completeMap;
    }

    // `BytemapInterface`
    public function insert(iterable $items, int $firstItemOffset = -1): void
    {
        if (-1 === $firstItemOffset || $firstItemOffset > $this->itemCount - 1) {
            $originalItemCount = $this->itemCount;

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
            $this->validateInsertedItems($itemCount, $this->itemCount - $itemCount, $originalItemCount, $this->itemCount - $originalItemCount);
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
            \array_splice($this->map, $firstItemOffset, 0, \is_array($items) ? $items : \iterator_to_array($items));
            $insertedItemCount = \count($this->map) - $itemCount;
            $this->itemCount += $insertedItemCount;
            $this->validateInsertedItems($firstItemOffset, $insertedItemCount, $firstItemOffset, $insertedItemCount);

            // Resize the bytemap if the negative first item offset is greater than the new item count.
            if (-$originalFirstItemOffset > $this->itemCount) {
                $overflow = -$originalFirstItemOffset - $this->itemCount - ($insertedItemCount > 0 ? 0 : 1);
                if ($overflow > 0) {
                    \array_splice($this->map, $insertedItemCount, 0, \array_fill(0, $overflow, $this->defaultItem));
                    $this->itemCount += $overflow;
                }
            }
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
            $listener = new BytemapListener(static function (string $value, ?int $key) use ($bytemap) {
                if (null === $key) {
                    $bytemap[] = $value;
                } else {
                    $bytemap[$key] = $value;
                }
            });
            self::parseJsonStreamOnline($jsonStream, $listener);
        } else {
            $bytemap->map = self::parseJsonStreamNatively($jsonStream);
            self::validateMapAndGetMaxKey($bytemap->map, $defaultItem);
            $bytemap->deriveProperties();
        }

        return $bytemap;
    }

    // `AbstractBytemap`
    protected function createEmptyMap(): void
    {
        $this->map = [];
    }

    protected function deleteWithNonNegativeOffset(int $firstItemOffset, int $howMany, int $itemCount): void
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
        parent::deriveProperties();

        $this->itemCount = self::getMaxKey($this->map) + 1;
    }

    protected function findArrayItems(
        array $items,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        $defaultItem = $this->defaultItem;
        $map = $this->map;
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
        // \preg_grep is not faster as of PHP 7.1.18.

        $lookup = [];
        $lookupSize = 0;
        $match = null;

        $defaultItem = $this->defaultItem;
        $map = $this->map;
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
        if (!\is_array($this->map)) {
            $reason = 'Failed to unserialize (the internal representation of a bytemap must be of type array, '.\gettype($this->map).' given)';

            throw new \TypeError(self::EXCEPTION_PREFIX.$reason);
        }

        $bytesPerItem = \strlen($this->defaultItem);
        foreach ($this->map as $offset => $item) {
            if (!\is_int($offset)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (index must be of type int, '.\gettype($offset).' given)');
            }
            if ($offset < 0) {
                throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Failed to unserialize (negative index: '.$offset.')');
            }

            if (!\is_string($item)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be of type string, '.\gettype($item).' given)');
            }
            if (\strlen($item) !== $bytesPerItem) {
                throw new \LengthException(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be exactly '.$bytesPerItem.' bytes, '.\strlen($item).' given)');
            }
        }
    }

    // `ArrayBytemap`
    protected function fillAndSort(): void
    {
        if (\count($this->map) !== $this->itemCount) {
            $this->map += \array_fill(0, $this->itemCount, $this->defaultItem);
        }
        \ksort($this->map, \SORT_NUMERIC);
    }

    protected function validateInsertedItems(
        int $firstItemOffsetToCheck,
        int $howManyToCheck,
        int $firstItemOffsetToRollBack,
        int $howManyToRollBack
        ): void {
        $bytesPerItem = $this->bytesPerItem;
        $lastItemOffsetToCheck = $firstItemOffsetToCheck + $howManyToCheck;
        for ($offset = $firstItemOffsetToCheck; $offset < $lastItemOffsetToCheck; ++$offset) {
            if (!\is_string($item = $this->map[$offset])) {
                $this->delete($firstItemOffsetToRollBack, $howManyToRollBack);

                throw new \TypeError(self::EXCEPTION_PREFIX.'Value must be of type string, '.\gettype($item).' given');
            }
            if (\strlen($item) !== $bytesPerItem) {
                $this->delete($firstItemOffsetToRollBack, $howManyToRollBack);

                throw new \LengthException(self::EXCEPTION_PREFIX.'Value must be exactly '.$bytesPerItem.' bytes, '.\strlen($item).' given');
            }
        }
    }
}

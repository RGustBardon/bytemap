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

use Bytemap\JsonListener\BytemapListener;

/**
 * An implementation of the `BytemapInterface` using a string.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
class Bytemap extends AbstractBytemap
{
    private $bytesInTotal;
    private $singleByte;

    // `ArrayAccess`
    public function offsetGet($offset): string
    {
        if (\is_int($offset) && $offset >= 0 && $offset < $this->itemCount) {
            if ($this->singleByte) {
                return $this->map[$offset];
            }

            return \substr($this->map, $offset * $this->bytesPerItem, $this->bytesPerItem);
        }

        self::throwOnOffsetGet($offset);
    }

    public function offsetSet($offset, $item): void
    {
        if (null === $offset) {  // `$bytemap[] = $item`
            $offset = $this->itemCount;
        }

        if (\is_int($offset) && $offset >= 0 && \is_string($item) && \strlen($item) === $this->bytesPerItem) {
            $unassignedCount = $offset - $this->itemCount;
            $bytesPerItem = $this->bytesPerItem;
            if ($unassignedCount < 0) {
                // Case 1. Overwrite an existing item.
                if ($this->singleByte) {
                    $this->map[$offset] = $item;
                } else {
                    $itemIndex = 0;
                    $offset *= $bytesPerItem;
                    do {
                        $this->map[$offset++] = $item[$itemIndex++];
                    } while ($itemIndex < $bytesPerItem);
                }
            } elseif (0 === $unassignedCount) {
                // Case 2. Append an item right after the last one.
                $this->map .= $item;
                ++$this->itemCount;
                $this->bytesInTotal += $bytesPerItem;
            } else {
                // Case 3. Append to a gap after the last item. Fill the gap with default items.
                $this->map .= \str_repeat($this->defaultItem, $unassignedCount).$item;
                $this->itemCount += $unassignedCount + 1;
                $this->bytesInTotal = $this->itemCount * $bytesPerItem;
            }
        } else {
            self::throwOnOffsetSet($offset, $item);
        }
    }

    public function offsetUnset($offset): void
    {
        if (\is_int($offset) && $offset >= 0 && $offset < $this->itemCount) {
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

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        $itemCount = $this->itemCount;
        $map = $this->map;
        if ($this->singleByte) {
            for ($i = 0; $i < $itemCount; ++$i) {
                yield $i => $map[$i];
            }
        } else {
            $bytesPerItem = (int) $this->bytesPerItem;
            for ($i = 0, $offset = 0; $i < $itemCount; ++$i, $offset += $bytesPerItem) {
                yield $i => \substr($map, $offset, $bytesPerItem);
            }
        }
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        if (0 === $this->itemCount) {
            return [];
        }

        $data = \str_split($this->map, $this->bytesPerItem);

        // @codeCoverageIgnoreStart
        if (false === $data) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.'\\str_split returned false when serializing to JSON');
        }
        // @codeCoverageIgnoreEnd

        return $data;
    }

    // `BytemapInterface`
    public function insert(iterable $items, int $firstItemOffset = -1): void
    {
        $substring = '';

        $bytesPerItem = $this->bytesPerItem;
        foreach ($items as $item) {
            if (\is_string($item) && \strlen($item) === $bytesPerItem) {
                $substring .= $item;
            } elseif (\is_string($item)) {
                throw new \LengthException(self::EXCEPTION_PREFIX.'Value must be exactly '.$bytesPerItem.' bytes, '.\strlen($item).' given');
            } else {
                throw new \TypeError(self::EXCEPTION_PREFIX.'Value must be of type string, '.\gettype($item).' given');
            }
        }

        if (-1 === $firstItemOffset || $firstItemOffset > $this->itemCount - 1) {
            // Insert the items.
            $padLength = \strlen($substring) + \max(0, $firstItemOffset - $this->itemCount) * $this->bytesPerItem;
            $this->map .= \str_pad($substring, (int) $padLength, $this->defaultItem, \STR_PAD_LEFT);
        } else {
            $originalFirstItemOffset = $firstItemOffset;
            // Calculate the positive offset corresponding to the negative one.
            if ($firstItemOffset < 0) {
                $firstItemOffset += $this->itemCount;

                // Keep the offsets within the bounds.
                if ($firstItemOffset < 0) {
                    $firstItemOffset = 0;
                }
            }

            // Resize the bytemap if the negative first item offset is greater than the new item count.
            $insertedItemCount = (int) (\strlen($substring) / $this->bytesPerItem);
            $newItemCount = $this->itemCount + $insertedItemCount;
            if (-$originalFirstItemOffset > $newItemCount) {
                $overflow = -$originalFirstItemOffset - $newItemCount - ($insertedItemCount > 0 ? 0 : 1);
                $padLength = ($overflow + $insertedItemCount) * $this->bytesPerItem;
                $substring = \str_pad($substring, $padLength, $this->defaultItem, \STR_PAD_RIGHT);
            }

            // Insert the items.
            $this->map = \substr_replace($this->map, $substring, $firstItemOffset * $this->bytesPerItem, 0);
        }

        $this->deriveProperties();
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
            $map = \json_decode(\stream_get_contents($jsonStream), true);
            self::ensureJsonDecodedSuccessfully($defaultItem, $map);

            $size = \count($map);
            if ($size > 0) {
                $maxKey = self::getMaxKey($map);
                if ($maxKey + 1 === $size) {
                    $bytemap->map = \implode('', $map);
                } else {
                    $bytemap[$maxKey] = $map[$maxKey];  // Avoid unnecessary resizing.
                    foreach ($map as $key => $value) {
                        $bytemap[$key] = $value;
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
        $this->map = '';
    }

    protected function deleteWithNonNegativeOffset(int $firstItemOffset, int $howMany, int $itemCount): void
    {
        $maximumRange = $itemCount - $firstItemOffset;
        if ($howMany >= $maximumRange) {
            $this->map = \substr($this->map, 0, $firstItemOffset * $this->bytesPerItem);
        } else {
            $this->map = \substr_replace($this->map, '', $firstItemOffset * $this->bytesPerItem, $howMany * $this->bytesPerItem);
        }

        $this->deriveProperties();
    }

    protected function deriveProperties(): void
    {
        parent::deriveProperties();

        $this->singleByte = 1 === $this->bytesPerItem;
        $this->bytesInTotal = \strlen($this->map);
        $this->itemCount = $this->bytesInTotal / $this->bytesPerItem;
    }

    protected function findArrayItems(
        array $items,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        if ($this->singleByte) {
            yield from $this->findBytes($items, $whitelist, $howManyToReturn, $howManyToSkip);
        } else {
            yield from $this->findSubstrings($items, $whitelist, $howManyToReturn, $howManyToSkip);
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

        $bytesPerItem = $this->bytesPerItem;
        $itemCount = $this->itemCount;
        $map = $this->map;
        if ($howManyToReturn > 0) {
            for ($i = $howManyToSkip, $offset = $howManyToSkip * $bytesPerItem; $i < $itemCount; ++$i, $offset += $bytesPerItem) {
                if (!($whitelist xor $lookup[$item = \substr($map, $offset, $bytesPerItem)] ?? ($match = \preg_match($regex, $item)))) {
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
            $i = $this->itemCount - 1 - $howManyToSkip;
            for ($offset = $i * $bytesPerItem; $i >= 0; --$i, $offset -= $bytesPerItem) {
                if (!($whitelist xor $lookup[$item = \substr($map, $offset, $bytesPerItem)] ?? ($match = \preg_match($regex, $item)))) {
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

    // `Bytemap`
    protected function findBytes(
        array $items,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        // \strspn and \strcspn are not faster as of PHP 7.1.18.

        $map = $this->map;
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

    protected function findSubstrings(
        array $items,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        $bytesPerItem = $this->bytesPerItem;
        $map = $this->map;
        if ($howManyToReturn > 0) {
            if ($whitelist) {
                for ($i = $howManyToSkip, $itemCount = $this->itemCount; $i < $itemCount; ++$i) {
                    if (isset($items[$item = \substr($map, $i * $bytesPerItem, $bytesPerItem)])) {
                        yield $i => $item;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            } else {
                for ($i = $howManyToSkip, $itemCount = $this->itemCount; $i < $itemCount; ++$i) {
                    if (!isset($items[$item = \substr($map, $i * $bytesPerItem, $bytesPerItem)])) {
                        yield $i => $item;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            }
        } elseif ($whitelist) {
            for ($i = $this->itemCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (isset($items[$item = \substr($map, $i * $bytesPerItem, $bytesPerItem)])) {
                    yield $i => $item;
                    if (0 === ++$howManyToReturn) {
                        return;
                    }
                }
            }
        } else {
            for ($i = $this->itemCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (!isset($items[$item = \substr($map, $i * $bytesPerItem, $bytesPerItem)])) {
                    yield $i => $item;
                    if (0 === ++$howManyToReturn) {
                        return;
                    }
                }
            }
        }
    }

    protected function validateUnserializedItems(): void
    {
        if (!\is_string($this->map)) {
            $reason = 'Failed to unserialize (the internal representation of a bytemap must be of type string, '.\gettype($this->map).' given)';

            throw new \TypeError(self::EXCEPTION_PREFIX.$reason);
        }
        if (0 !== \strlen($this->map) % \strlen($this->defaultItem)) {
            $reason = 'Failed to unserialize (the length of the internal string, '.\strlen($this->map).', is not a multiple of the length of the default item, '.\strlen($this->defaultItem).')';

            throw new \LengthException(self::EXCEPTION_PREFIX.$reason);
        }
    }
}

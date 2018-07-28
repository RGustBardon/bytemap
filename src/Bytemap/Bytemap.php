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
use JsonStreamingParser\Parser;

/**
 * An implementation of the `BytemapInterface` using a string.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
class Bytemap extends AbstractBytemap
{
    private $bytesInTotal = 0;
    private $bytesPerItem;

    public function __construct($defaultItem)
    {
        parent::__construct($defaultItem);

        $this->bytesPerItem = \strlen($defaultItem);
    }

    // `ArrayAccess`
    public function offsetGet($offset): string
    {
        if (1 === $this->bytesPerItem) {
            return $this->map[$offset];
        }

        return \substr($this->map, $offset * $this->bytesPerItem, $this->bytesPerItem);
    }

    public function offsetSet($offset, $item): void
    {
        if (null === $offset) {  // `$bytemap[] = $item`
            $offset = $this->itemCount;
        }

        /** @var int $unassignedCount */
        $unassignedCount = $offset - $this->itemCount;
        $bytesPerItem = $this->bytesPerItem;
        if ($unassignedCount < 0) {
            // Case 1. Overwrite an existing item.
            if (1 === $bytesPerItem) {
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
    }

    public function offsetUnset($offset): void
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

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        $bytesPerItem = (int) $this->bytesPerItem;
        $itemCount = $this->itemCount;
        $map = $this->map;
        if (1 === $bytesPerItem) {
            for ($i = 0; $i < $itemCount; ++$i) {
                yield $i => $map[$i];
            }
        } else {
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
            throw new \UnexpectedValueException('Bytemap: \\str_split returned false when serializing to JSON');
        }
        // @codeCoverageIgnoreEnd

        return $data;
    }

    // `BytemapInterface`
    public function insert(iterable $items, int $firstItemOffset = -1): void
    {
        $items = \implode('', \is_array($items) ? $items : \iterator_to_array($items));
        if (-1 === $firstItemOffset || $firstItemOffset > $this->itemCount - 1) {
            // Insert the items.
            $padLength = \strlen($items) + \max(0, $firstItemOffset - $this->itemCount) * $this->bytesPerItem;
            $this->map .= \str_pad($items, (int) $padLength, $this->defaultItem, \STR_PAD_LEFT);
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
            $insertedItemCount = (int) (\strlen($items) / $this->bytesPerItem);
            $newItemCount = $this->itemCount + $insertedItemCount;
            if (-$originalFirstItemOffset > $newItemCount) {
                $overflow = -$originalFirstItemOffset - $newItemCount - ($insertedItemCount > 0 ? 0 : 1);
                $padLength = ($overflow + $insertedItemCount) * $this->bytesPerItem;
                $items = \str_pad($items, $padLength, $this->defaultItem, \STR_PAD_RIGHT);
            }

            // Insert the items.
            $this->map = \substr_replace($this->map, $items, $firstItemOffset * $this->bytesPerItem, 0);
        }

        $this->deriveProperties();
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
            $map = \json_decode(\stream_get_contents($jsonStream), true);
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

    protected function deriveProperties(): void
    {
        $this->bytesPerItem = \strlen($this->defaultItem);
        $this->bytesInTotal = \strlen($this->map);
        $this->itemCount = $this->bytesInTotal / $this->bytesPerItem;
    }
}

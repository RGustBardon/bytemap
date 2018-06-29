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
class Bytemap extends AbstractBytemap
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

    // `ArrayAccess`
    public function offsetExists($offset): bool
    {
        return $offset < $this->itemCount;
    }

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
        if (0 > $unassignedCount) {
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

    // `Countable`
    public function count(): int
    {
        return $this->itemCount;
    }

    // `IteratorAggregate`
    public function getIterator(): \Generator
    {
        return (static function (self $bytemap): \Generator {
            if (1 === $bytemap->bytesPerItem) {
                for ($i = 0; $i < $bytemap->itemCount; ++$i) {
                    yield $i => $bytemap->map[$i];
                }
            } else {
                for ($i = 0; $i < $bytemap->itemCount; ++$i) {
                    yield $i => $bytemap[$i];
                }
            }
        })(clone $this);
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        return [$this->defaultItem, \str_split($this->map, $this->bytesPerItem)];
    }

    // `Serializable`
    public function serialize(): string
    {
        return \serialize([$this->defaultItem, $this->map]);
    }

    public function unserialize($serialized)
    {
        [$this->defaultItem, $this->map] = \unserialize($serialized, ['allowed_classes' => false]);
        $this->deriveProperties();
    }

    // `BytemapInterface`
    public static function parseJsonStream($jsonStream, bool $useStreamingParser = true): BytemapInterface
    {
        if ($useStreamingParser && \class_exists('\\JsonStreamingParser\\Parser')) {
            return self::parseBytemapJsonOnTheFly($jsonStream, __CLASS__);
        }

        [$defaultItem, $map] = \json_decode(\stream_get_contents($jsonStream), true);
        $bytemap = new self($defaultItem);
        $cnt = \count($map);
        if ($cnt > 0) {
            // `\max(\array_keys($map))` would affect peak memory usage.
            $maxKey = -1;
            foreach ($map as $key => $value) {
                if ($maxKey < $key) {
                    $maxKey = $key;
                }
            }
            if (\count($map) === $maxKey + 1) {
                $bytemap->map = \implode('', $map);
            } else {
                $bytemap[$maxKey] = $map[$maxKey];  // Avoid unnecessary resizing.
                foreach ($map as $key => $value) {
                    $bytemap[$key] = $value;
                }
            }
            $bytemap->deriveProperties();
        }

        return $bytemap;
    }

    private function deriveProperties(): void
    {
        $this->bytesPerItem = \strlen($this->defaultItem);
        $this->bytesInTotal = \strlen($this->map);
        $this->itemCount = $this->bytesInTotal / $this->bytesPerItem;
    }
}

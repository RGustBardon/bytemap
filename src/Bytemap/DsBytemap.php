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
 * An implementation of the `BytemapInterface` using `\Ds\Vector`.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
class DsBytemap extends AbstractBytemap
{
    private $defaultItem;

    private $itemCount = 0;
    private $map;

    public function __construct($defaultItem)
    {
        $this->defaultItem = $defaultItem;

        $this->map = new \Ds\Vector();
    }

    // `ArrayAccess`
    public function offsetExists($offset): bool
    {
        return $offset < $this->itemCount;
    }

    public function offsetGet($offset): string
    {
        return $this->map[$offset];
    }

    public function offsetSet($offset, $item): void
    {
        if (null === $offset) {  // `$bytemap[] = $item`
            $offset = $this->itemCount;
        }

        $unassignedCount = $offset - $this->itemCount;
        if ($unassignedCount < 0) {
            // Case 1. Overwrite an existing item.
            $this->map[$offset] = $item;
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
    }

    public function offsetUnset($offset): void
    {
        if ($this->itemCount > $offset) {
            if ($this->itemCount - 1 === $offset) {
                unset($this->map[$offset]);
                --$this->itemCount;
            } else {
                $this->map[$offset] = $this->defaultItem;
            }
        }
    }

    // `Countable`
    public function count(): int
    {
        return $this->itemCount;
    }

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        return clone $this->map;
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        return [$this->defaultItem, $this->map->toArray()];
    }

    // `Serializable`
    public function serialize(): string
    {
        return \serialize([$this->defaultItem, $this->map]);
    }

    public function unserialize($serialized)
    {
        [$this->defaultItem, $this->map] = \unserialize($serialized, ['allowed_classes' => ['Ds\\Vector']]);
        $this->deriveProperties();
    }

    // `BytemapInterface`
    public static function parseJsonStream($jsonStream, bool $useStreamingParser = true): BytemapInterface
    {
        if ($useStreamingParser && \class_exists('\\JsonStreamingParser\\Parser')) {
            $bytemap = null;
            $maxKey = -1;
            $listener = new BytemapListener(function ($value, $key) use (&$bytemap, &$maxKey) {
                if (null === $bytemap) {
                    $bytemap = new self($value);
                } elseif (null === $key) {
                    $bytemap[] = $value;
                } else {
                    $unassignedCount = $key - $maxKey - 1;
                    if (0 > $unassignedCount) {
                        $bytemap[$key] = $value;
                    } else {
                        if (0 < $unassignedCount) {
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
            (new Parser($jsonStream, $listener))->parse();

            return $bytemap;
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
            $bytemap->map->allocate($maxKey + 1);
            for ($i = 0; $i < $maxKey; ++$i) {
                $bytemap[] = $defaultItem;
            }
            foreach ($map as $key => $value) {
                $bytemap[$key] = $value;
            }
            $bytemap->deriveProperties();
        }

        return $bytemap;
    }

    private function deriveProperties(): void
    {
        $this->itemCount = \count($this->map);
    }
}

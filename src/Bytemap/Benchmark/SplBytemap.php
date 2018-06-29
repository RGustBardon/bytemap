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
    private $defaultItem;

    private $itemCount = 0;
    private $map;

    public function __construct($defaultItem)
    {
        $this->defaultItem = $defaultItem;

        $this->map = new \SplFixedArray(0);
    }

    public function __clone()
    {
        $this->map = clone $this->map;
    }

    // `ArrayAccess`
    public function offsetExists($offset): bool
    {
        return $offset < $this->itemCount;
    }

    public function offsetGet($offset): string
    {
        return $this->map[$offset] ?? $this->defaultItem;
    }

    public function offsetSet($offset, $item): void
    {
        if (null === $offset) {  // `$bytemap[] = $item`
            $offset = $this->itemCount;
        }

        $unassignedCount = $offset - $this->itemCount;
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

    // `Countable`
    public function count(): int
    {
        return $this->itemCount;
    }

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        return (static function (self $bytemap): \Generator {
            for ($i = 0; $i < $bytemap->itemCount; ++$i) {
                yield $i => $bytemap[$i];
            }
        })(clone $this);
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
        [$this->defaultItem, $this->map] = \unserialize($serialized, ['allowed_classes' => ['SplFixedArray']]);
        $this->map->__wakeup();
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
                            $bytemap->map->setSize($maxKey + 1);
                        }
                        $bytemap[] = $value;
                        $maxKey = $key;
                    }
                }
            });
            (new Parser($jsonStream, $listener))->parse();

            if (null === $bytemap) {
                throw new \UnexpectedValueException('Bytemap: corrupted JSON stream');
            }

            return $bytemap;
        }

        [$defaultItem, $map] = \json_decode(\stream_get_contents($jsonStream), true);
        $bytemap = new self($defaultItem);
        if ($map) {
            $bytemap->map = \SplFixedArray::fromArray($map);
            $bytemap->deriveProperties();
        }

        return $bytemap;
    }

    private function deriveProperties(): void
    {
        $this->itemCount = \count($this->map);
    }
}

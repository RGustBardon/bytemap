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
    private $defaultItem;

    private $itemCount = 0;
    private $map = [];

    public function __construct($defaultItem)
    {
        $this->defaultItem = $defaultItem;
    }

    // `ArrayAccess`
    public function offsetExists($offset): bool
    {
        return $offset < $this->itemCount;
    }

    public function offsetGet($offset)
    {
        return $this->map[$offset] ?? $this->defaultItem;
    }

    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {  // `$bytemap[] = $item`
            $offset = $this->itemCount;
        }

        $this->map[$offset] = $value;
        if ($this->itemCount < $offset + 1) {
            $this->itemCount = $offset + 1;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->map[$offset]);
        if ($this->itemCount - 1 === $offset) {
            --$this->itemCount;
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
            for ($i = 0; $i < $bytemap->itemCount; ++$i) {
                yield $i => $bytemap[$i];
            }
        })(clone $this);
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        \ksort($this->map, \SORT_NUMERIC);

        return [$this->defaultItem, $this->map];
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
        $bytemap->map = $map;
        $bytemap->deriveProperties();

        return $bytemap;
    }

    private function deriveProperties(): void
    {
        $this->itemCount = \max(\array_keys($this->map)) + 1;
    }
}

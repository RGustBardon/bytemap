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

    // `Serializable`
    public function unserialize($serialized)
    {
        [$this->defaultItem, $this->map] =
            \unserialize($serialized, ['allowed_classes' => self::UNSERIALIZED_CLASSES]);
        $this->map->__wakeup();
        $this->deriveProperties();
    }

    // `BytemapInterface`
    public static function parseJsonStream($jsonStream, $defaultItem): BytemapInterface
    {
        $bytemap = new self($defaultItem);
        if (self::hasStreamingParser()) {
            $maxKey = -1;
            $listener = new BytemapListener(function ($value, $key) use ($bytemap, &$maxKey) {
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
            (new Parser($jsonStream, $listener))->parse();
        } else {
            $map = \json_decode(\stream_get_contents($jsonStream), true);
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

    protected function deriveProperties(): void
    {
        $this->itemCount = \count($this->map);
    }
}
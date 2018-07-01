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

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        if (1 < $this->bytesPerItem) {
            return parent::getIterator();
        }

        return (static function (self $bytemap): \Generator {
            for ($i = 0; $i < $bytemap->itemCount; ++$i) {
                yield $i => $bytemap->map[$i];
            }
        })(clone $this);
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

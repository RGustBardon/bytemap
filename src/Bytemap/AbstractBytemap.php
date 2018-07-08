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
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
abstract class AbstractBytemap implements BytemapInterface
{
    protected const UNSERIALIZED_CLASSES = false;

    protected $defaultItem;

    protected $itemCount = 0;
    protected $map;

    public function __construct($defaultItem)
    {
        $this->defaultItem = $defaultItem;

        $this->createEmptyMap();
    }

    // Property overloading.
    public function __get($name)
    {
        throw new \ErrorException(\sprintf('Undefined property: %s::$%s', static::class, $name));
    }

    public function __set($name, $value): void
    {
        self::__get($name);
    }

    public function __isset($name): bool
    {
        self::__get($name);
    }

    public function __unset($name): void
    {
        self::__get($name);
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
        $completeMap = [];
        for ($i = 0; $i < $this->itemCount; ++$i) {
            $completeMap[$i] = $this[$i];
        }

        return $completeMap;
    }

    // `Serializable`
    public function serialize(): string
    {
        return \serialize([$this->defaultItem, $this->map]);
    }

    public function unserialize($serialized)
    {
        [$this->defaultItem, $this->map] =
            \unserialize($serialized, ['allowed_classes' => static::UNSERIALIZED_CLASSES]);
        $this->deriveProperties();
    }

    // `BytemapInterface`
    public function find(?iterable $items = null, bool $whitelist = true, int $howMany = \PHP_INT_MAX): \Generator
    {
        if (0 === $howMany) {
            return;
        }

        if (null === $items) {
            $needles = [$this->defaultItem => true];
            $whiteList = !$whitelist;
        } else {
            $needles = [];
            foreach ($items as $value) {
                if (\is_string($value)) {
                    $needles[$value] = true;
                }
            }
        }

        yield from $this->findArrayItems($needles, $whitelist, $howMany);
    }

    public function grep(string $regex, bool $whitelist = true, int $howMany = \PHP_INT_MAX): \Generator
    {
        if (0 === $howMany) {
            return;
        }

        if (!isset($this->defaultItem[1])) {
            $whitelistNeedles = [];
            $blacklistNeedles = [];
            for ($i = 0; $i < 256; ++$i) {
                $needle = chr($i);
                if ($whitelist xor \preg_match($regex, $needle)) {
                    $blacklistNeedles[$needle] = true;
                } else {
                    $whitelistNeedles[$needle] = true;
                }
            }
            $whitelist = \count($whitelistNeedles) <= 128;
            yield from $this->findArrayItems($whitelist ? $whitelistNeedles : $blacklistNeedles, $whitelist, $howMany);
        } elseif ($howMany > 0) {
            foreach ($this as $key => $item) {
                if (!($whitelist xor \preg_match($regex, $item))) {
                    yield $key => $item;
                    if (0 === --$howMany) {
                        break;
                    }
                }
            }
        } else {
            for ($i = $this->itemCount - 1; $i >= 0; --$i) {
                if (!($whitelist xor \preg_match($regex, $this[$i]))) {
                    yield $i => $this[$i];
                    if (0 === ++$howMany) {
                        break;
                    }
                }
            }
        }
    }

    public function insert(iterable $items, int $firstItemOffset = -1): void
    {
        $originalFirstItemOffset = $firstItemOffset;

        // Resize the bytemap if the positive first item offset is greater than the item count.
        if ($firstItemOffset > $this->itemCount) {
            $this[$firstItemOffset - 1] = $this->defaultItem;
        }

        // Calculate the positive offset corresponding to the negative one.
        if (0 > $firstItemOffset) {
            $firstItemOffset += $this->itemCount;
        }

        // Keep the offsets within the bounds.
        $firstItemOffset = \max(0, $firstItemOffset);

        // Add the items.
        $originalItemCount = $this->itemCount;
        foreach ($items as $item) {
            $this[] = $item;
        }

        // Resize the bytemap if the negative first item offset is greater than the new item count.
        if ($originalFirstItemOffset < 0 && \abs($originalFirstItemOffset) > $this->itemCount) {
            $lastItemOffset = \abs($originalFirstItemOffset) - ($this->itemCount > $originalItemCount ? 1 : 2);
            if ($lastItemOffset >= $this->itemCount) {
                $this[$lastItemOffset] = $this->defaultItem;
            }
        }

        // The juggling algorithm.
        $n = $this->itemCount - $firstItemOffset;
        $shift = $n - $this->itemCount + $originalItemCount;
        $gcd = self::calculateGreatestCommonDivisor($n, $shift);

        for ($i = 0; $i < $gcd; ++$i) {
            $tmp = $this[$firstItemOffset + $i];
            $j = $i;
            while (true) {
                $k = $j + $shift;
                if ($k >= $n) {
                    $k -= $n;
                }
                if ($k === $i) {
                    break;
                }
                $this[$firstItemOffset + $j] = $this[$firstItemOffset + $k];
                $j = $k;
            }
            $this[$firstItemOffset + $j] = $tmp;
        }
    }

    public function delete(int $firstItemOffset = -1, int $howMany = \PHP_INT_MAX): void
    {
        // Check if there is anything to delete.
        if (1 > $howMany || 0 === $this->itemCount) {
            return;
        }

        // Calculate the positive offset corresponding to the negative one.
        if (0 > $firstItemOffset) {
            $firstItemOffset += $this->itemCount;
        }

        // Keep the offsets within the bounds.
        $firstItemOffset = \max(0, $firstItemOffset);
        $howMany = \min($howMany, $this->itemCount - $firstItemOffset);

        $howManyLeft = $howMany;

        // Shift all the subsequent items left by the numbers of items deleted.
        $lastItemOffset = $firstItemOffset + $howMany - 1;
        for ($i = $this->itemCount - 1; $i > $lastItemOffset; --$i, --$howManyLeft) {
            $this[$i - $howMany] = $this[$i];
            unset($this[$i]);
        }

        // If there are still items to be deleted, delete the trailing ones.
        for (; $howManyLeft > 0; --$howManyLeft) {
            unset($this[$this->itemCount - 1]);
        }
    }

    public function streamJson($stream): void
    {
        \fwrite($stream, '[');
        for ($i = 0; $i < $this->itemCount - 1; ++$i) {
            \fwrite($stream, \json_encode($this[$i]).',');
        }
        \fwrite($stream, ($this->itemCount > 0 ? \json_encode($this[$i]) : '').']');
    }

    protected function findArrayItems(array $items, bool $whitelist, int $howMany): \Generator
    {
        if ($howMany > 0) {
            foreach ($this as $key => $item) {
                if (!($whitelist xor isset($items[$item]))) {
                    yield $key => $item;
                    if (0 === --$howMany) {
                        break;
                    }
                }
            }
        } else {
            for ($i = $this->itemCount - 1; $i >= 0; --$i) {
                if (!($whitelist xor isset($items[$this[$i]]))) {
                    yield $i => $this[$i];
                    if (0 === ++$howMany) {
                        break;
                    }
                }
            }
        }
    }

    // `AbstractBytemap`
    protected static function hasStreamingParser(): bool
    {
        return ($_ENV['BYTEMAP_STREAMING_PARSER'] ?? true) && \class_exists('\\JsonStreamingParser\\Parser');
    }

    protected static function getMaxKey(iterable $map): int
    {
        // `\max(\array_keys($map))` would affect peak memory usage.
        $maxKey = -1;
        foreach ($map as $key => $value) {
            if ($maxKey < $key) {
                $maxKey = $key;
            }
        }

        return $maxKey;
    }

    abstract protected function createEmptyMap(): void;

    abstract protected function deriveProperties(): void;

    private static function calculateGreatestCommonDivisor(int $a, int $b): int
    {
        while (0 !== $b) {
            $tmp = $b;
            $b = $a % $b;
            $a = $tmp;
        }

        return $a;
    }
}

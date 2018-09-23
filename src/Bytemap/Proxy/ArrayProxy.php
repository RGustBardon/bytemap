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

namespace Bytemap\Proxy;

use Bytemap\Bytemap;
use Bytemap\BytemapInterface;

class ArrayProxy extends AbstractProxy implements ArrayProxyInterface
{
    public function __construct(string $defaultItem, string ...$values)
    {
        $this->bytemap = new Bytemap($defaultItem);
        $this->bytemap->insert($values);
    }

    public function __clone()
    {
        $this->bytemap = clone $this->bytemap;
    }

    // `ArrayProxyInterface`: Bytemap encapsulation
    public static function wrap(BytemapInterface $bytemap): ArrayProxyInterface
    {
        /** @var ArrayProxyInterface $arrayProxy */
        $arrayProxy = self::wrapGenerically($bytemap);
        \count($arrayProxy);  // PHP CS Fixer.

        return $arrayProxy;
    }

    // `ArrayProxyInterface`: Array conversion
    public function exportArray(): array
    {
        return $this->jsonSerialize();
    }

    public static function import(string $defaultItem, iterable $items): ArrayProxyInterface
    {
        return new self($defaultItem, ...$items);
    }

    // `ArrayProxyInterface`: Array API
    public function chunk(int $size, bool $preserveKeys = false): \Generator
    {
        if ($size < 1) {
            throw new \OutOfRangeException('Size parameter expected to be greater than 0');
        }

        $chunk = [];
        $itemCount = 0;
        foreach ($this->bytemap as $key => $value) {
            if ($preserveKeys) {
                $chunk[$key] = $value;
            } else {
                $chunk[] = $value;
            }
            ++$itemCount;
            if ($size === $itemCount) {
                yield $chunk;
                $chunk = [];
                $itemCount = 0;
            }
        }
        if ($chunk) {
            yield $chunk;
        }
    }

    public function countValues(): array
    {
        $values = [];
        foreach ($this->bytemap as $value) {
            if (!isset($values[$value])) {
                $values[$value] = 0;
            }
            ++$values[$value];
        }

        return $values;
    }

    public function inArray(string $needle): bool
    {
        return (bool) \iterator_to_array($this->bytemap->find([$needle], true, 1));
    }

    public function keyExists(int $key): bool
    {
        return $key >= 0 && $key < \count($this->bytemap);
    }

    public function keyFirst(): ?int
    {
        return \count($this->bytemap) > 0 ? 0 : null;
    }

    public function keyLast(): ?int
    {
        $itemCount = \count($this->bytemap);

        return $itemCount > 0 ? $itemCount - 1 : null;
    }

    public function keys(?string $searchValue = null): \Generator
    {
        if (null === $searchValue) {
            for ($i = 0, $itemCount = \count($this->bytemap); $i < $itemCount; ++$i) {
                yield $i;
            }
        } else {
            foreach ($this->bytemap->find([$searchValue]) as $key => $value) {
                yield $key;
            }
        }
    }

    public function merge(iterable ...$iterables): ArrayProxyInterface
    {
        $clone = clone $this;

        foreach ($iterables as $iterable) {
            foreach ($iterable as $value) {
                $clone->bytemap[] = $value;
            }
        }

        return $clone;
    }

    public function natCaseSort(): void
    {
        if (!\defined('\\SORT_NATURAL') || !\is_callable('\\strnatcasecmp')) {
            throw new \RuntimeException('Natural order comparator is not available');
        }
        self::sortBytemapByItem($this->bytemap, self::getComparator(\SORT_NATURAL | \SORT_FLAG_CASE));
    }

    public function natSort(): void
    {
        if (!\defined('\\SORT_NATURAL') || !\is_callable('\\strnatcmp')) {
            throw new \RuntimeException('Natural order comparator is not available');
        }
        self::sortBytemapByItem($this->bytemap, self::getComparator(\SORT_NATURAL));
    }

    public function pad(int $size, string $value): ArrayProxyInterface
    {
        $itemCount = \count($this->bytemap);
        $itemPendingCount = \abs($size) - $itemCount;
        $clone = clone $this;

        if ($itemPendingCount > 0) {
            $clone->bytemap->insert((function () use ($itemPendingCount, $value): \Generator {
                for ($i = 0; $i < $itemPendingCount; ++$i) {
                    yield $value;
                }
            })(), $size > 0 ? -1 : -$itemCount - $itemPendingCount);
        }

        return $clone;
    }

    public function pop(): ?string
    {
        $itemCount = \count($this->bytemap);
        if ($itemCount > 0) {
            $item = $this->bytemap[$itemCount - 1];
            unset($this->bytemap[$itemCount - 1]);

            return $item;
        }

        return null;
    }

    public function push(string ...$values): int
    {
        $this->bytemap->insert($values);

        return \count($this->bytemap);
    }

    public function rand(int $num = 1)
    {
        $itemCount = \count($this->bytemap);

        if (1 === $num) {
            return \mt_rand(0, $itemCount - 1);
        }

        if ($num === $itemCount) {
            return \range(0, $itemCount - 1);
        }

        if ($num < 1 || $num > $itemCount) {
            throw new \OutOfRangeException('Argument has to be between 1 and the number of elements');
        }

        return \array_rand(\range(0, $itemCount - 1), $num);
    }

    public function reduce(callable $callback, $initial = null)
    {
        foreach ($this->bytemap as $value) {
            $initial = $callback($initial, $value);
        }

        return $initial;
    }

    public function reverse(): ArrayProxyInterface
    {
        $lastOffset = \count($this->bytemap) - 1;
        $clone = clone $this;
        foreach ($this->bytemap as $offset => $item) {
            $clone[$lastOffset - $offset] = $item;
        }

        return $clone;
    }

    public function rSort(int $sortFlags = \SORT_REGULAR): void
    {
        self::sortBytemapByItem($this->bytemap, self::getComparator($sortFlags, true));
    }

    public function search(string $needle)
    {
        return \array_keys(\iterator_to_array($this->bytemap->find([$needle], true, 1)))[0] ?? false;
    }

    public function shift(): ?string
    {
        if (\count($this->bytemap) > 0) {
            $item = $this->bytemap[0];
            unset($this->bytemap[0]);

            return $item;
        }

        return null;
    }

    public function slice(int $offset, ?int $length = null): ArrayProxyInterface
    {
        $itemCount = \count($this->bytemap);
        $clone = clone $this;

        if ($offset < 0) {
            $offset += $itemCount;
        }

        if (null === $length) {
            $length = $itemCount;
        } elseif ($length < 0) {
            $length += $itemCount - $offset;
        }

        $clone->bytemap->delete($offset + $length);
        $clone->bytemap->delete(0, $offset);

        return $clone;
    }

    public function shuffle(): void
    {
        // Fisher-Yates.
        $nLeft = \count($this->bytemap);
        while (--$nLeft) {
            $rndIdx = \mt_rand(0, $nLeft);
            if ($rndIdx !== $nLeft) {
                $tmp = $this->bytemap[$nLeft];
                $this->bytemap[$nLeft] = $this->bytemap[$rndIdx];
                $this->bytemap[$rndIdx] = $tmp;
            }
        }
    }

    public function sort(int $sortFlags = \SORT_REGULAR): void
    {
        self::sortBytemapByItem($this->bytemap, self::getComparator($sortFlags));
    }

    public function unique(int $sortFlags = \SORT_STRING): \Generator
    {
        if (\defined('\\SORT_LOCALE_STRING') && \SORT_LOCALE_STRING === $sortFlags) {
            yield from \array_unique($this->exportArray(), \SORT_LOCALE_STRING);
        } else {
            $seen = [];
            switch ($sortFlags) {
                case \SORT_NUMERIC:
                    foreach ($this->bytemap as $key => $value) {
                        $mapKey = ' '.(float) $value;
                        if (!isset($seen[$mapKey])) {
                            yield $key => $value;
                            $seen[$mapKey] = true;
                        }
                    }
                    break;

                case \SORT_REGULAR:
                    foreach ($this->bytemap as $key => $value) {
                        $mapKey = ' '.(\is_numeric($value) ? (float) $value : $value);
                        if (!isset($seen[$mapKey])) {
                            yield $key => $value;
                            $seen[$mapKey] = true;
                        }
                    }
                    break;

                default:
                    foreach ($this->bytemap as $key => $value) {
                        if (!isset($seen[$value])) {
                            yield $key => $value;
                            $seen[$value] = true;
                        }
                    }
                    break;
            }
        }
    }

    public function unshift(string ...$values): int
    {
        $this->bytemap->insert($values, 0);

        return \count($this->bytemap);
    }

    public function usort(callable $valueCompareFunc): void
    {
        self::sortBytemapByItem($this->bytemap, $valueCompareFunc);
    }

    public function values(): \Generator
    {
        /** @var \Generator $generator */
        $generator = $this->bytemap->getIterator();
        \count($this->bytemap);  // PHP CS Fixer vs. PHPStan.

        return $generator;
    }

    public static function combine(string $defaultItem, iterable $keys, iterable $values): ArrayProxyInterface
    {
        $arrayProxy = new self($defaultItem);
        $bytemap = new Bytemap($defaultItem);
        $bytemap->insert($values);
        $offset = 0;

        try {
            foreach ($keys as $key) {
                $arrayProxy->bytemap[$key] = $bytemap[$offset];
                ++$offset;
            }
        } catch (\OutOfRangeException $e) {
        }

        if (isset($e) || \count($bytemap) !== $offset) {
            throw new \UnderflowException('Both parameters should have an equal number of elements');
        }

        return $arrayProxy;
    }

    public static function fill(string $defaultItem, int $startIndex, int $num, ?string $value = null): ArrayProxyInterface
    {
        if ($startIndex < 0) {
            throw new \OutOfRangeException('Start index can\'t be negative');
        }

        if ($num < 0) {
            throw new \OutOfRangeException('Number of elements can\'t be negative');
        }

        if (null === $value) {
            $value = $defaultItem;
        }

        $arrayProxy = new self($defaultItem);
        $arrayProxy->bytemap->insert((function () use ($num, $value): \Generator {
            for ($i = 0; $i < $num; ++$i) {
                yield $value;
            }
        })(), $startIndex);

        return $arrayProxy;
    }

    public static function fillKeys(string $defaultItem, iterable $keys, ?string $value = null): ArrayProxyInterface
    {
        if (null === $value) {
            $value = $defaultItem;
        }

        $arrayProxy = new self($defaultItem);
        if (\is_array($keys)) {
            $arrayProxy[\max($keys)] = $value;
        }
        foreach ($keys as $key) {
            $arrayProxy[$key] = $value;
        }

        return $arrayProxy;
    }
}

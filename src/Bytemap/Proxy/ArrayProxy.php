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
    public function __construct(string $defaultElement, string ...$values)
    {
        $this->bytemap = new Bytemap($defaultElement);
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

    /**
     * @param string[] $elements
     */
    public static function import(string $defaultElement, iterable $elements): ArrayProxyInterface
    {
        return new self($defaultElement, ...$elements);
    }

    // `ArrayProxyInterface`: Array API
    public function chunk(int $size, bool $preserveKeys = false): \Generator
    {
        if ($size < 1) {
            throw new \OutOfRangeException('Size parameter expected to be greater than 0');
        }

        $chunk = [];
        $elementCount = 0;
        foreach ($this->bytemap as $key => $value) {
            if ($preserveKeys) {
                $chunk[$key] = $value;
            } else {
                $chunk[] = $value;
            }
            ++$elementCount;
            if ($size === $elementCount) {
                yield $chunk;
                $chunk = [];
                $elementCount = 0;
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

    public function diff(iterable ...$iterables): \Generator
    {
        $blacklist = [];
        foreach ($iterables as $iterable) {
            foreach ($iterable as $value) {
                $blacklist[$value] = true;
            }
        }

        foreach ($this->bytemap as $index => $value) {
            if (!isset($blacklist[$value])) {
                yield $index => $value;
            }
        }
    }

    public function filter(?callable $callback = null, int $flag = 0): \Generator
    {
        if (null === $callback) {
            yield from $this->bytemap->find(['0'], false);
        } else {
            switch ($flag) {
                case \ARRAY_FILTER_USE_KEY:
                    $clone = clone $this->bytemap;
                    for ($i = 0, $elementCount = \count($this->bytemap); $i < $elementCount; ++$i) {
                        if ($callback($i)) {
                            yield $i => $clone[$i];
                        }
                    }

                    break;
                case \ARRAY_FILTER_USE_BOTH:
                    foreach ($this->bytemap as $index => $element) {
                        if ($callback($element, $index)) {
                            yield $index => $element;
                        }
                    }

                    break;
                default:
                    foreach ($this->bytemap as $index => $element) {
                        if ($callback($element)) {
                            yield $index => $element;
                        }
                    }

                    break;
            }
        }
    }

    public function inArray(string $needle): bool
    {
        return (bool) \iterator_to_array($this->bytemap->find([$needle], true, 1));
    }

    public function keyExists(int $key): bool
    {
        return $key >= 0 && $key < \count($this->bytemap);
    }

    public function keyFirst(): int
    {
        if (\count($this->bytemap) > 0) {
            return 0;
        }

        throw new \UnderflowException('Iterable is empty');
    }

    public function keyLast(): int
    {
        $elementCount = \count($this->bytemap);
        if ($elementCount > 0) {
            return $elementCount - 1;
        }

        throw new \UnderflowException('Iterable is empty');
    }

    public function keys(?string $searchValue = null): \Generator
    {
        if (null === $searchValue) {
            for ($i = 0, $elementCount = \count($this->bytemap); $i < $elementCount; ++$i) {
                yield $i;
            }
        } else {
            foreach ($this->bytemap->find([$searchValue]) as $key => $value) {
                yield $key;
            }
        }
    }

    public function map(?callable $callback, iterable ...$arguments): \Generator
    {
        if ($arguments) {
            $multipleIterator = new \MultipleIterator(\MultipleIterator::MIT_NEED_ANY | \MultipleIterator::MIT_KEYS_NUMERIC);
            /** @var \Iterator $bytemapIterator */
            $bytemapIterator = $this->bytemap->getIterator();
            $multipleIterator->attachIterator($bytemapIterator);
            foreach ($arguments as $column) {
                if (\is_array($column)) {
                    $multipleIterator->attachIterator(new \ArrayIterator($column));

                    continue;
                }
                if ($column instanceof \Iterator) {
                    $multipleIterator->attachIterator($column);

                    continue;
                }
                if ($column instanceof \IteratorAggregate) {
                    $columnIterator = $column->getIterator();
                    if ($columnIterator instanceof \Iterator) {
                        $multipleIterator->attachIterator($columnIterator);

                        continue;
                    }
                }
                // @codeCoverageIgnoreStart
                throw new \InvalidArgumentException('Unsupported iterable');
                // @codeCoverageIgnoreEnd
            }
            if (null === $callback) {
                foreach ($multipleIterator as $value) {
                    yield $value;
                }
            } else {
                foreach ($multipleIterator as $value) {
                    yield $callback(...$value);
                }
            }
        } elseif (null === $callback) {
            foreach ($this->bytemap as $value) {
                yield $value;
            }
        } else {
            foreach ($this->bytemap as $value) {
                yield $callback($value);
            }
        }
    }

    public function merge(iterable ...$iterables): \Generator
    {
        $clone = clone $this->bytemap;
        yield from $clone;
        $index = \count($clone) - 1;

        foreach ($iterables as $iterable) {
            foreach ($iterable as $value) {
                yield ++$index => $value;
            }
        }
    }

    public function natCaseSort(): void
    {
        if (!\defined('\\SORT_NATURAL') || !\is_callable('\\strnatcasecmp')) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('Natural order comparator is not available');
            // @codeCoverageIgnoreEnd
        }
        self::sortBytemapByElement($this->bytemap, self::getComparator(\SORT_NATURAL | \SORT_FLAG_CASE));
    }

    public function natSort(): void
    {
        if (!\defined('\\SORT_NATURAL') || !\is_callable('\\strnatcmp')) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('Natural order comparator is not available');
            // @codeCoverageIgnoreEnd
        }
        self::sortBytemapByElement($this->bytemap, self::getComparator(\SORT_NATURAL));
    }

    public function pad(int $size, string $value): \Generator
    {
        $clone = clone $this->bytemap;
        $elementCount = \count($clone);
        $elementPendingCount = \abs($size) - $elementCount;

        if ($elementPendingCount <= 0) {
            yield from $clone->getIterator();
        } elseif ($size > 0) {
            yield from $clone->getIterator();

            for ($end = $elementCount + $elementPendingCount; $elementCount < $end; ++$elementCount) {
                yield $elementCount => $value;
            }
        } else {
            for ($i = 0; $i < $elementPendingCount; ++$i) {
                yield $i => $value;
            }

            foreach ($clone as $index => $bytemapValue) {
                yield $index + $i => $bytemapValue;
            }
        }
    }

    public function pop(): string
    {
        $elementCount = \count($this->bytemap);
        if ($elementCount > 0) {
            $element = $this->bytemap[$elementCount - 1];
            unset($this->bytemap[$elementCount - 1]);

            return $element;
        }

        throw new \UnderflowException('Iterable is empty');
    }

    public function push(string ...$values): int
    {
        $this->bytemap->insert($values);

        return \count($this->bytemap);
    }

    public function rand(int $num = 1)
    {
        $elementCount = \count($this->bytemap);

        if (0 === $elementCount) {
            throw new \UnderflowException('Iterable is empty');
        }

        if (1 === $num) {
            return \mt_rand(0, $elementCount - 1);
        }

        if ($num === $elementCount) {
            return \range(0, $elementCount - 1);
        }

        if ($num < 1 || $num > $elementCount) {
            throw new \OutOfRangeException('Argument has to be between 1 and the number of elements');
        }

        return \array_rand(\range(0, $elementCount - 1), $num);
    }

    public function reduce(callable $callback, $initial = null)
    {
        foreach ($this->bytemap as $value) {
            $initial = $callback($initial, $value);
        }

        return $initial;
    }

    public function replace(iterable ...$iterables): ArrayProxyInterface
    {
        $clone = clone $this;
        foreach ($iterables as $iterable) {
            foreach ($iterable as $key => $value) {
                $clone->bytemap[$key] = $value;
            }
        }

        return $clone;
    }

    public function reverse(bool $preserveKeys = false): \Generator
    {
        $clone = $this->bytemap;
        $i = \count($clone) - 1;

        if ($preserveKeys) {
            for (; $i >= 0; --$i) {
                yield $i => $clone[$i];
            }
        } else {
            for (; $i >= 0; --$i) {
                yield $clone[$i];
            }
        }
    }

    public function rSort(int $sortFlags = \SORT_REGULAR): void
    {
        self::sortBytemapByElement($this->bytemap, self::getComparator($sortFlags, true));
    }

    public function search(string $needle)
    {
        return \array_keys(\iterator_to_array($this->bytemap->find([$needle], true, 1)))[0] ?? false;
    }

    public function shift(): string
    {
        if (\count($this->bytemap) > 0) {
            $element = $this->bytemap[0];
            unset($this->bytemap[0]);

            return $element;
        }

        throw new \UnderflowException('Iterable is empty');
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

    public function sizeOf(): int
    {
        return \count($this->bytemap);
    }

    public function slice(int $index, ?int $length = null, bool $preserveKeys = false): \Generator
    {
        $elementCount = \count($this->bytemap);
        [$index, $length] = self::calculateIndexAndLength($elementCount, $index, $length);

        $sliceIndex = $preserveKeys ? $index : 0;
        for ($i = $index, $end = $index + $length; $i < $end; ++$i, ++$sliceIndex) {
            yield $sliceIndex => $this->bytemap[$i];
        }
    }

    public function sort(int $sortFlags = \SORT_REGULAR): void
    {
        self::sortBytemapByElement($this->bytemap, self::getComparator($sortFlags));
    }

    public function splice(int $index, ?int $length = null, $replacement = []): ArrayProxyInterface
    {
        $elementCount = \count($this->bytemap);

        [$index, $length] = self::calculateIndexAndLength($elementCount, $index, $length);
        if (!\is_iterable($replacement)) {
            $replacement = (array) $replacement;
        }

        $extracted = $this->createEmptyBytemap();
        for ($i = $index, $end = $index + $length; $i < $end; ++$i) {
            $extracted[] = $this->bytemap[$i];
        }

        $this->bytemap->insert($replacement, $index);
        $this->bytemap->delete((int) $index + \count($this->bytemap) - $elementCount, $length);

        return self::wrap($extracted);
    }

    public function union(iterable ...$iterables): ArrayProxyInterface
    {
        $clone = clone $this;
        $elementCount = \count($clone->bytemap);
        foreach ($iterables as $iterable) {
            foreach ($iterable as $key => $value) {
                if (!\is_int($key)) {
                    throw new \TypeError('Index must be of type integer, '.\gettype($key).' given');
                }

                if ($key < 0) {
                    throw new \OutOfRangeException('Negative index: '.$key);
                }

                if ($key >= $elementCount) {
                    $clone->bytemap[$key] = $value;
                }
            }
            $elementCount = \count($clone->bytemap);
        }

        return $clone;
    }

    public function unique(int $sortFlags = \SORT_STRING): \Generator
    {
        if (\defined('\\SORT_LOCALE_STRING') && \SORT_LOCALE_STRING === $sortFlags) {
            // @codeCoverageIgnoreStart
            yield from \array_unique($this->exportArray(), \SORT_LOCALE_STRING);
        // @codeCoverageIgnoreEnd
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

    public function uSort(callable $valueCompareFunc): void
    {
        self::sortBytemapByElement($this->bytemap, $valueCompareFunc);
    }

    public function values(): \Generator
    {
        /** @var \Generator $generator */
        $generator = $this->bytemap->getIterator();
        \count($this->bytemap);  // PHP CS Fixer vs. PHPStan.

        return $generator;
    }

    public function walk(callable $callback, $userdata = null): void
    {
        $passedToCallback = 2 + (\func_num_args() > 1 ? 1 : 0);

        try {
            $reflection = new \ReflectionMethod(...(array) $callback);
        } catch (\TypeError | \ReflectionException $e) {
            if (\is_string($callback) || \is_object($callback) && $callback instanceof \Closure) {
                try {
                    $reflection = new \ReflectionFunction($callback);
                    // @codeCoverageIgnoreStart
                } catch (\TypeError | \ReflectionException $e) {
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        if (isset($reflection)) {
            $expectedByCallback = $reflection->getNumberOfParameters();
            if ($expectedByCallback > $passedToCallback) {
                $message = 'Too few arguments to function %s(), %d passed and exactly %d expected';

                throw new \ArgumentCountError(\sprintf($message, $reflection->getName(), $passedToCallback, $expectedByCallback));
            }
        }

        if (3 === $passedToCallback) {
            foreach ($this->bytemap as $originalIndex => $originalElement) {
                $index = $originalIndex;
                $element = $originalElement;
                $callback($element, $index, $userdata);
                if ($originalElement !== $element) {
                    $this->bytemap[$originalIndex] = $element;
                }
            }
        } else {
            foreach ($this->bytemap as $originalIndex => $originalElement) {
                $index = $originalIndex;
                $element = $originalElement;
                $callback($element, $index);
                if ($originalElement !== $element) {
                    $this->bytemap[$originalIndex] = $element;
                }
            }
        }
    }

    public static function combine(string $defaultElement, iterable $keys, iterable $values): ArrayProxyInterface
    {
        $arrayProxy = new self($defaultElement);
        $bytemap = new Bytemap($defaultElement);
        $bytemap->insert($values);
        $index = 0;

        try {
            foreach ($keys as $key) {
                $arrayProxy->bytemap[$key] = $bytemap[$index];
                ++$index;
            }
        } catch (\OutOfRangeException $e) {
        }

        if (isset($e) || \count($bytemap) !== $index) {
            throw new \UnderflowException('Both parameters should have an equal number of elements');
        }

        return $arrayProxy;
    }

    public static function fill(string $defaultElement, int $startIndex, int $num, ?string $value = null): ArrayProxyInterface
    {
        if ($startIndex < 0) {
            throw new \OutOfRangeException('Start index can\'t be negative');
        }

        if ($num < 0) {
            throw new \OutOfRangeException('Number of elements can\'t be negative');
        }

        if (null === $value) {
            $value = $defaultElement;
        }

        $arrayProxy = new self($defaultElement);
        $arrayProxy->bytemap->insert((function () use ($num, $value): \Generator {
            for ($i = 0; $i < $num; ++$i) {
                yield $value;
            }
        })(), $startIndex);

        return $arrayProxy;
    }

    public static function fillKeys(string $defaultElement, iterable $keys, ?string $value = null): ArrayProxyInterface
    {
        if (null === $value) {
            $value = $defaultElement;
        }

        $arrayProxy = new self($defaultElement);
        if (\is_array($keys)) {
            $arrayProxy[\max($keys)] = $value;
        }
        foreach ($keys as $key) {
            $arrayProxy[$key] = $value;
        }

        return $arrayProxy;
    }

    // `ArrayProxyInterface`: PCRE API
    public function pregFilter($pattern, $replacement, int $limit = -1, ?int &$count = 0): \Generator
    {
        $count = 0;
        foreach ($this->bytemap->grep(\is_iterable($pattern) ? $pattern : [$pattern]) as $index => $element) {
            $element = \preg_replace($pattern, $replacement, $element, $limit, $countInIteration);
            $count += $countInIteration;
            yield $index => $element;
        }
    }

    public function pregGrep(string $pattern, int $flags = 0): \Generator
    {
        yield from $this->bytemap->grep([$pattern], !($flags & \PREG_GREP_INVERT));
    }

    public function pregReplace($pattern, $replacement, int $limit = -1, ?int &$count = 0): \Generator
    {
        $clone = clone $this->bytemap;
        $lastIndex = 0;
        foreach ($this->pregFilter($pattern, $replacement, $limit, $count) as $index => $element) {
            for (; $lastIndex < $index; ++$lastIndex) {
                yield $lastIndex => $clone[$lastIndex];
            }
            $lastIndex = $index + 1;
            yield $index => $element;
        }
        for ($elementCount = \count($clone); $lastIndex < $elementCount; ++$lastIndex) {
            yield $lastIndex => $clone[$lastIndex];
        }
    }

    public function pregReplaceCallback($pattern, callable $callback, int $limit = -1, ?int &$count = 0): \Generator
    {
        $count = 0;

        if (\is_iterable($pattern)) {
            if (!\is_array($pattern)) {
                $pattern = \iterator_to_array($pattern);
            }

            if (!$pattern) {
                yield from $this->bytemap->getIterator();

                return;
            }
        }

        $clone = clone $this->bytemap;
        $lastIndex = 0;
        foreach ($clone->grep(\is_iterable($pattern) ? $pattern : [$pattern]) as $index => $element) {
            for (; $lastIndex < $index; ++$lastIndex) {
                yield $lastIndex => $clone[$lastIndex];
            }
            $lastIndex = $index + 1;
            $element = \preg_replace_callback($pattern, $callback, $element, $limit, $countInIteration);
            $count += $countInIteration;
            yield $index => $element;
        }
        for ($elementCount = \count($clone); $lastIndex < $elementCount; ++$lastIndex) {
            yield $lastIndex => $clone[$lastIndex];
        }
    }

    public function pregReplaceCallbackArray(iterable $patternsAndCallbacks, int $limit = -1, ?int &$count = 0): \Generator
    {
        $count = 0;

        if (!\is_array($patternsAndCallbacks)) {
            $patternsAndCallbacks = \iterator_to_array($patternsAndCallbacks);
        }

        if (!$patternsAndCallbacks) {
            return;
        }

        if (1 === \count($patternsAndCallbacks)) {
            $callback = \reset($patternsAndCallbacks);
            yield from $this->pregReplaceCallback(\key($patternsAndCallbacks), $callback, $limit, $count);

            return;
        }

        $clone = clone $this->bytemap;
        $lastIndex = 0;
        foreach ($clone->grep(\array_keys($patternsAndCallbacks)) as $index => $element) {
            for (; $lastIndex < $index; ++$lastIndex) {
                yield $lastIndex => $clone[$lastIndex];
            }
            $lastIndex = $index + 1;
            $element = \preg_replace_callback_array($patternsAndCallbacks, $element, $limit, $countInIteration);
            $count += $countInIteration;
            yield $index => $element;
        }
        for ($elementCount = \count($clone); $lastIndex < $elementCount; ++$lastIndex) {
            yield $lastIndex => $clone[$lastIndex];
        }
    }

    // `ArrayProxyInterface`: String API
    public function implode(string $glue): string
    {
        $result = '';
        foreach ($this->bytemap as $element) {
            $result .= $element.$glue;
        }

        return ('' === $glue || '' === $result) ? $result : \substr($result, 0, -\strlen($glue));
    }

    public function join(string $glue): string
    {
        return $this->implode($glue);
    }

    protected static function calculateIndexAndLength(int $elementCount, int $index, ?int $length): array
    {
        if ($index < 0) {
            $index += $elementCount;
        }
        $index = \max(0, \min($elementCount - 1, $index));

        if (null === $length) {
            $length = $elementCount;
        } elseif ($length < 0) {
            $length += $elementCount - $index;
        }
        $length = \min($length, $elementCount - $index);

        return [$index, $length];
    }
}

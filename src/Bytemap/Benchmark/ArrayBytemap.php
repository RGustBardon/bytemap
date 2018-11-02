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
    // `ArrayAccess`
    public function offsetGet($index): string
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            return $this->map[$index] ?? $this->defaultElement;
        }

        self::throwOnOffsetGet($index);
    }

    public function offsetSet($index, $element): void
    {
        if (null === $index) {  // `$bytemap[] = $element`
            $index = $this->elementCount;
        }

        if (\is_int($index) && $index >= 0 && \is_string($element) && \strlen($element) === $this->bytesPerElement) {
            $this->map[$index] = $element;
            if ($this->elementCount < $index + 1) {
                $this->elementCount = $index + 1;
            }
        } else {
            self::throwOnOffsetSet($index, $element, $this->bytesPerElement);
        }
    }

    public function offsetUnset($index): void
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            if ($this->elementCount - 1 === $index) {
                --$this->elementCount;
                unset($this->map[$index]);
            } else {
                $this->fillAndSort();
                \array_splice($this->map, $index, 1);
                $this->map = \array_diff($this->map, [$this->defaultElement]);
                --$this->elementCount;
                if (!isset($this->map[$this->elementCount - 1])) {
                    $this->map[$this->elementCount - 1] = $this->defaultElement;
                }
            }
        }
    }

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        return (static function (self $bytemap): \Generator {
            for ($i = 0, $elementCount = $bytemap->elementCount, $defaultElement = $bytemap->defaultElement; $i < $elementCount; ++$i) {
                yield $i => $bytemap->map[$i] ?? $defaultElement;
            }
        })(clone $this);
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        $completeMap = [];
        for ($i = 0, $elementCount = $this->elementCount, $defaultElement = $this->defaultElement; $i < $elementCount; ++$i) {
            $completeMap[$i] = $this->map[$i] ?? $defaultElement;
        }

        return $completeMap;
    }

    // `BytemapInterface`
    public function insert(iterable $elements, int $firstIndex = -1): void
    {
        if (-1 === $firstIndex || $firstIndex > $this->elementCount - 1) {
            $originalElementCount = $this->elementCount;

            // Resize the bytemap if the positive first element index is greater than the element count.
            if ($firstIndex > $this->elementCount) {
                $this[$firstIndex - 1] = $this->defaultElement;
            }

            // Append the elements.
            $elementCount = \count($this->map);

            try {
                \array_push($this->map, ...$elements);
            } catch (\ArgumentCountError $e) {
            }
            $this->elementCount += \count($this->map) - $elementCount;
            $this->validateInsertedElements($elementCount, $this->elementCount - $elementCount, $originalElementCount, $this->elementCount - $originalElementCount);
        } else {
            $this->fillAndSort();

            $originalFirstIndex = $firstIndex;
            // Calculate the positive index corresponding to the negative one.
            if ($firstIndex < 0) {
                $firstIndex += $this->elementCount;

                // Keep the indices within the bounds.
                if ($firstIndex < 0) {
                    $firstIndex = 0;
                }
            }

            // Insert the elements.
            $elementCount = \count($this->map);
            \array_splice($this->map, $firstIndex, 0, \is_array($elements) ? $elements : \iterator_to_array($elements));
            $insertedElementCount = \count($this->map) - $elementCount;
            $this->elementCount += $insertedElementCount;
            $this->validateInsertedElements($firstIndex, $insertedElementCount, $firstIndex, $insertedElementCount);

            // Resize the bytemap if the negative first element index is greater than the new element count.
            if (-$originalFirstIndex > $this->elementCount) {
                $overflow = -$originalFirstIndex - $this->elementCount - ($insertedElementCount > 0 ? 0 : 1);
                if ($overflow > 0) {
                    \array_splice($this->map, $insertedElementCount, 0, \array_fill(0, $overflow, $this->defaultElement));
                    $this->elementCount += $overflow;
                }
            }
        }
    }

    public function streamJson($stream): void
    {
        self::ensureStream($stream);

        $defaultElement = $this->defaultElement;

        $buffer = '[';
        for ($i = 0, $penultimate = $this->elementCount - 1; $i < $penultimate; ++$i) {
            $buffer .= \json_encode($this->map[$i] ?? $defaultElement).',';
            if (\strlen($buffer) > self::STREAM_BUFFER_SIZE) {
                self::stream($stream, $buffer);
                $buffer = '';
            }
        }
        $buffer .= ($this->elementCount > 0 ? \json_encode($this->map[$i] ?? $defaultElement) : '').']';
        self::stream($stream, $buffer);
    }

    public static function parseJsonStream($jsonStream, $defaultElement): BytemapInterface
    {
        self::ensureStream($jsonStream);

        $bytemap = new self($defaultElement);
        if (self::hasStreamingParser()) {
            $listener = new BytemapListener(static function (string $value, ?int $key) use ($bytemap) {
                if (null === $key) {
                    $bytemap[] = $value;
                } else {
                    $bytemap[$key] = $value;
                }
            });
            self::parseJsonStreamOnline($jsonStream, $listener);
        } else {
            $bytemap->map = self::parseJsonStreamNatively($jsonStream);
            self::validateMapAndGetMaxKey($bytemap->map, $defaultElement);
            $bytemap->deriveProperties();
        }

        return $bytemap;
    }

    // `AbstractBytemap`
    protected function createEmptyMap(): void
    {
        $this->map = [];
    }

    protected function deleteWithNonNegativeIndex(int $firstIndex, int $howMany, int $elementCount): void
    {
        $maximumRange = $elementCount - $firstIndex;
        if ($howMany >= $maximumRange) {
            $this->elementCount -= $maximumRange;
            while (--$maximumRange >= 0) {
                unset($this->map[--$elementCount]);
            }
        } else {
            $this->fillAndSort();
            \array_splice($this->map, $firstIndex, $howMany);
            $this->map = \array_diff($this->map, [$this->defaultElement]);
            $this->elementCount -= $howMany;
            if (!isset($this->map[$this->elementCount - 1])) {
                $this->map[$this->elementCount - 1] = $this->defaultElement;
            }
        }
    }

    protected function deriveProperties(): void
    {
        parent::deriveProperties();

        $this->elementCount = self::getMaxKey($this->map) + 1;
    }

    protected function findArrayElements(
        array $elements,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        $defaultElement = $this->defaultElement;
        $map = $this->map;
        if ($howManyToReturn > 0) {
            if ($whitelist) {
                for ($i = $howManyToSkip, $elementCount = $this->elementCount; $i < $elementCount; ++$i) {
                    if (isset($elements[$element = $map[$i] ?? $defaultElement])) {
                        yield $i => $element;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            } else {
                for ($i = $howManyToSkip, $elementCount = $this->elementCount; $i < $elementCount; ++$i) {
                    if (!isset($elements[$element = $map[$i] ?? $defaultElement])) {
                        yield $i => $element;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            }
        } elseif ($whitelist) {
            for ($i = $this->elementCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (isset($elements[$element = $map[$i] ?? $defaultElement])) {
                    yield $i => $element;
                    if (0 === ++$howManyToReturn) {
                        return;
                    }
                }
            }
        } else {
            for ($i = $this->elementCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (!isset($elements[$element = $map[$i] ?? $defaultElement])) {
                    yield $i => $element;
                    if (0 === ++$howManyToReturn) {
                        return;
                    }
                }
            }
        }
    }

    protected function grepMultibyte(
        array $patterns,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        // \preg_grep is not faster as of PHP 7.1.18.

        $lookup = [];
        $lookupSize = 0;
        $match = null;

        $defaultElement = $this->defaultElement;
        $map = $this->map;
        if ($howManyToReturn > 0) {
            for ($i = $howManyToSkip, $elementCount = $this->elementCount; $i < $elementCount; ++$i) {
                if (!($whitelist xor $lookup[$element = $map[$i] ?? $defaultElement] ?? ($match = (null !== \preg_filter($patterns, '', $element))))) {
                    yield $i => $element;
                    if (0 === --$howManyToReturn) {
                        return;
                    }
                }
                if (null !== $match) {
                    $lookup[$element] = $match;
                    $match = null;
                    if ($lookupSize > self::GREP_MAXIMUM_LOOKUP_SIZE) {
                        unset($lookup[\key($lookup)]);
                    } else {
                        ++$lookupSize;
                    }
                }
            }
        } else {
            for ($i = $this->elementCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (!($whitelist xor $lookup[$element = $map[$i] ?? $defaultElement] ?? ($match = (null !== \preg_filter($patterns, '', $element))))) {
                    yield $i => $element;
                    if (0 === ++$howManyToReturn) {
                        return;
                    }
                }
                if (null !== $match) {
                    $lookup[$element] = $match;
                    $match = null;
                    if ($lookupSize > self::GREP_MAXIMUM_LOOKUP_SIZE) {
                        unset($lookup[\key($lookup)]);
                    } else {
                        ++$lookupSize;
                    }
                }
            }
        }
    }

    protected function validateUnserializedElements(): void
    {
        if (!\is_array($this->map)) {
            $reason = 'Failed to unserialize (the internal representation of a bytemap must be of type array, '.\gettype($this->map).' given)';

            throw new \TypeError(self::EXCEPTION_PREFIX.$reason);
        }

        $bytesPerElement = \strlen($this->defaultElement);
        foreach ($this->map as $index => $element) {
            if (!\is_int($index)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (index must be of type int, '.\gettype($index).' given)');
            }
            if ($index < 0) {
                throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Failed to unserialize (negative index: '.$index.')');
            }

            if (!\is_string($element)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be of type string, '.\gettype($element).' given)');
            }
            if (\strlen($element) !== $bytesPerElement) {
                throw new \DomainException(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be exactly '.$bytesPerElement.' bytes, '.\strlen($element).' given)');
            }
        }
    }

    // `ArrayBytemap`
    protected function fillAndSort(): void
    {
        if (\count($this->map) !== $this->elementCount) {
            $this->map += \array_fill(0, $this->elementCount, $this->defaultElement);
        }
        \ksort($this->map, \SORT_NUMERIC);
    }

    protected function validateInsertedElements(
        int $firstIndexToCheck,
        int $howManyToCheck,
        int $firstIndexToRollBack,
        int $howManyToRollBack
        ): void {
        $bytesPerElement = $this->bytesPerElement;
        $lastElementIndexToCheck = $firstIndexToCheck + $howManyToCheck;
        for ($index = $firstIndexToCheck; $index < $lastElementIndexToCheck; ++$index) {
            if (!\is_string($element = $this->map[$index])) {
                $this->delete($firstIndexToRollBack, $howManyToRollBack);

                throw new \TypeError(self::EXCEPTION_PREFIX.'Value must be of type string, '.\gettype($element).' given');
            }
            if (\strlen($element) !== $bytesPerElement) {
                $this->delete($firstIndexToRollBack, $howManyToRollBack);

                throw new \DomainException(self::EXCEPTION_PREFIX.'Value must be exactly '.$bytesPerElement.' bytes, '.\strlen($element).' given');
            }
        }
    }
}

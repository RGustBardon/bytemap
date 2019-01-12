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
 * An implementation of the `BytemapInterface` using `\SplFixedArray`.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
final class SplBytemap extends AbstractBytemap
{
    protected const UNSERIALIZED_CLASSES = ['SplFixedArray'];

    public function __clone()
    {
        $this->map = clone $this->map;
    }

    // `ArrayAccess`
    public function offsetGet($index): string
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            return $this->map[$index] ?? $this->defaultValue;
        }

        self::throwOnOffsetGet($index);
    }

    public function offsetSet($index, $element): void
    {
        if (null === $index) {  // `$bytemap[] = $element`
            $index = $this->elementCount;
        }

        if (\is_int($index) && $index >= 0 && \is_string($element) && \strlen($element) === $this->bytesPerElement) {
            $unassignedCount = $index - $this->elementCount;
            if ($unassignedCount >= 0) {
                $this->map->setSize($this->elementCount + $unassignedCount + 1);
                $this->elementCount += $unassignedCount + 1;
            }
            $this->map[$index] = $element;
        } else {
            self::throwOnOffsetSet($index, $element, $this->bytesPerElement);
        }
    }

    public function offsetUnset($index): void
    {
        $elementCount = $this->elementCount;
        if (\is_int($index) && $index >= 0 && $index < $elementCount) {
            // Shift all the subsequent elements left by one.
            for ($i = $index + 1; $i < $elementCount; ++$i) {
                $this->map[$i - 1] = $this->map[$i];
            }

            $this->map->setSize(--$this->elementCount);
        }
    }

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        return (static function (self $bytemap): \Generator {
            for ($i = 0, $defaultValue = $bytemap->defaultValue, $elementCount = $bytemap->elementCount; $i < $elementCount; ++$i) {
                yield $i => $bytemap->map[$i] ?? $defaultValue;
            }
        })(clone $this);
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        $completeMap = [];
        for ($i = 0, $defaultValue = $this->defaultValue, $elementCount = $this->elementCount; $i < $elementCount; ++$i) {
            $completeMap[$i] = $this->map[$i] ?? $defaultValue;
        }

        return $completeMap;
    }

    // `Serializable`
    public function unserialize($serialized)
    {
        $this->unserializeAndValidate($serialized);
        if (\is_object($this->map) && $this->map instanceof \SplFixedArray) {
            $this->map->__wakeup();
        }
        $this->validateUnserializedElements();
        $this->deriveProperties();
    }

    // `BytemapInterface`
    public function insert(iterable $elements, int $firstIndex = -1): void
    {
        $originalFirstIndex = $firstIndex;
        $elementCountBeforeResizing = $this->elementCount;

        // Resize the bytemap if the positive first element index is greater than the element count.
        if ($firstIndex > $this->elementCount) {
            $this[$firstIndex - 1] = $this->defaultValue;
        }

        // Allocate the memory.
        $newSize = $this->calculateNewSize($elements, $firstIndex);
        if (null !== $newSize) {
            $this->map->setSize($newSize);
        }

        // Calculate the positive index corresponding to the negative one.
        if ($firstIndex < 0) {
            $firstIndex += $this->elementCount;

            // Keep the indices within the bounds.
            if ($firstIndex < 0) {
                $firstIndex = 0;
            }
        }

        // Append the elements.
        $originalElementCount = $this->elementCount;
        if (isset($newSize)) {
            $bytesPerElement = $this->bytesPerElement;
            $elementCount = $originalElementCount;
            foreach ($elements as $element) {
                if (\is_string($element) && \strlen($element) === $bytesPerElement) {
                    $this->map[$elementCount] = $element;
                    ++$elementCount;
                } else {
                    $this->elementCount = $elementCount;
                    $this->delete($elementCountBeforeResizing);
                    if (\is_string($element)) {
                        throw new \DomainException(self::EXCEPTION_PREFIX.'Value must be exactly '.$bytesPerElement.' bytes, '.\strlen($element).' given');
                    }

                    throw new \TypeError(self::EXCEPTION_PREFIX.'Value must be of type string, '.\gettype($element).' given');
                }
            }
            $this->elementCount = $elementCount;
        } else {
            try {
                foreach ($elements as $element) {
                    $this[] = $element;
                }
            } catch (\TypeError | \DomainException $e) {
                $this->delete($elementCountBeforeResizing);

                throw $e;
            }
        }

        // Resize the bytemap if the negative first element index is greater than the new element count.
        if (-$originalFirstIndex > $this->elementCount) {
            $lastElementIndex = -$originalFirstIndex - ($this->elementCount > $originalElementCount ? 1 : 2);
            if ($lastElementIndex >= $this->elementCount) {
                $this[$lastElementIndex] = $this->defaultValue;
            }
        }

        // The juggling algorithm.
        $n = $this->elementCount - $firstIndex;
        $shift = $n - $this->elementCount + $originalElementCount;
        $gcd = self::calculateGreatestCommonDivisor($n, $shift);

        for ($i = 0; $i < $gcd; ++$i) {
            $tmp = $this->map[$firstIndex + $i];
            $j = $i;
            while (true) {
                $k = $j + $shift;
                if ($k >= $n) {
                    $k -= $n;
                }
                if ($k === $i) {
                    break;
                }
                $this->map[$firstIndex + $j] = $this->map[$firstIndex + $k];
                $j = $k;
            }
            $this->map[$firstIndex + $j] = $tmp;
        }
    }

    public function streamJson($stream): void
    {
        self::ensureStream($stream);

        $defaultValue = $this->defaultValue;

        $buffer = '[';
        for ($i = 0, $penultimate = $this->elementCount - 1; $i < $penultimate; ++$i) {
            $buffer .= \json_encode($this->map[$i] ?? $defaultValue).',';
            if (\strlen($buffer) > self::STREAM_BUFFER_SIZE) {
                self::stream($stream, $buffer);
                $buffer = '';
            }
        }
        $buffer .= ($this->elementCount > 0 ? \json_encode($this->map[$i] ?? $defaultValue) : '').']';
        self::stream($stream, $buffer);
    }

    public static function parseJsonStream($jsonStream, $defaultValue): BytemapInterface
    {
        self::ensureStream($jsonStream);

        $bytemap = new self($defaultValue);
        if (self::hasStreamingParser()) {
            $maxKey = -1;
            $listener = new BytemapListener(static function (?int $key, string $value) use ($bytemap, &$maxKey): void {
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
            self::parseJsonStreamOnline($jsonStream, $listener);
        } else {
            $map = self::parseJsonStreamNatively($jsonStream);
            self::validateMapAndGetMaxKey($map, $defaultValue);
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

    protected function deleteWithNonNegativeIndex(int $firstIndex, int $howMany, int $elementCount): void
    {
        // Keep the indices within the bounds.
        $howMany = \min($howMany, $elementCount - $firstIndex);

        // Shift all the subsequent elements left by the number of elements deleted.
        for ($i = $firstIndex + $howMany; $i < $elementCount; ++$i) {
            $this->map[$i - $howMany] = $this->map[$i];
        }

        // Delete the trailing elements.
        $this->elementCount -= $howMany;
        $this->map->setSize($this->elementCount);
    }

    protected function deriveProperties(): void
    {
        parent::deriveProperties();

        $this->elementCount = \count($this->map);
    }

    protected function findArrayElements(
        array $elements,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        $defaultValue = $this->defaultValue;
        $map = clone $this->map;
        if ($howManyToReturn > 0) {
            if ($whitelist) {
                for ($i = $howManyToSkip, $elementCount = $this->elementCount; $i < $elementCount; ++$i) {
                    if (isset($elements[$element = $map[$i] ?? $defaultValue])) {
                        yield $i => $element;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            } else {
                for ($i = $howManyToSkip, $elementCount = $this->elementCount; $i < $elementCount; ++$i) {
                    if (!isset($elements[$element = $map[$i] ?? $defaultValue])) {
                        yield $i => $element;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            }
        } elseif ($whitelist) {
            for ($i = $this->elementCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (isset($elements[$element = $map[$i] ?? $defaultValue])) {
                    yield $i => $element;
                    if (0 === ++$howManyToReturn) {
                        return;
                    }
                }
            }
        } else {
            for ($i = $this->elementCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (!isset($elements[$element = $map[$i] ?? $defaultValue])) {
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
        $lookup = [];
        $lookupSize = 0;
        $match = null;

        $defaultValue = $this->defaultValue;
        $map = clone $this->map;
        if ($howManyToReturn > 0) {
            for ($i = $howManyToSkip, $elementCount = $this->elementCount; $i < $elementCount; ++$i) {
                if (!($whitelist xor $lookup[$element = $map[$i] ?? $defaultValue] ?? ($match = (null !== \preg_filter($patterns, '', $element))))) {
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
                if (!($whitelist xor $lookup[$element = $map[$i] ?? $defaultValue] ?? ($match = (null !== \preg_filter($patterns, '', $element))))) {
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
        if (!\is_object($this->map) || !($this->map instanceof \SplFixedArray)) {
            $reason = 'Failed to unserialize (the internal representation of a bytemap must be an SplFixedArray, '.\gettype($this->map).' given)';

            throw new \TypeError(self::EXCEPTION_PREFIX.$reason);
        }

        $bytesPerElement = \strlen($this->defaultValue);
        foreach ($this->map as $element) {
            if (null !== $element) {
                if (!\is_string($element)) {
                    throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be of type string, '.\gettype($element).' given)');
                }
                if (\strlen($element) !== $bytesPerElement) {
                    throw new \DomainException(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be exactly '.$bytesPerElement.' bytes, '.\strlen($element).' given)');
                }
            }
        }
    }
}

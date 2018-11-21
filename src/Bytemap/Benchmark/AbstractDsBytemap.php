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
 * An abstract implementation of the `BytemapInterface` using `\Ds\Sequence`.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
abstract class AbstractDsBytemap extends AbstractBytemap
{
    public function __clone()
    {
        $this->map = clone $this->map;
    }

    // `ArrayAccess`
    public function offsetGet($index): string
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            return $this->map[$index];
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
            if ($unassignedCount < 0) {
                // Case 1. Overwrite an existing element.
                $this->map[$index] = $element;
            } elseif (0 === $unassignedCount) {
                // Case 2. Append an element right after the last one.
                $this->map[] = $element;
                ++$this->elementCount;
            } else {
                // Case 3. Append to a gap after the last element. Fill the gap with default values.
                $this->map->allocate($this->elementCount + $unassignedCount + 1);
                for ($i = 0; $i < $unassignedCount; ++$i) {
                    $this->map[] = $this->defaultValue;
                }
                $this->map[] = $element;
                $this->elementCount += $unassignedCount + 1;
            }
        } else {
            self::throwOnOffsetSet($index, $element, $this->bytesPerElement);
        }
    }

    public function offsetUnset($index): void
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            --$this->elementCount;
            unset($this->map[$index]);
        }
    }

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        return clone $this->map;
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        return $this->map->toArray();
    }

    // `BytemapInterface`
    public function insert(iterable $elements, int $firstIndex = -1): void
    {
        $originalElementCount = $this->elementCount;

        // Resize the bytemap if the positive first element index is greater than the element count.
        if ($firstIndex > $this->elementCount) {
            $this[$firstIndex - 1] = $this->defaultValue;
        }

        // Allocate the memory.
        $newSize = $this->calculateNewSize($elements, $firstIndex);
        if (null !== $newSize) {
            $this->map->allocate($newSize);
        }

        if (-1 === $firstIndex || $firstIndex > $this->elementCount - 1) {
            $firstIndexToCheck = $this->elementCount;

            // Append the elements.
            $this->map->push(...$elements);

            $this->elementCount = \count($this->map);
            $this->validateInsertedElements($firstIndexToCheck, $this->elementCount - $firstIndexToCheck, $originalElementCount, $this->elementCount - $originalElementCount);
        } else {
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
            $this->map->insert($firstIndex, ...$elements);
            $insertedElementCount = \count($this->map) - $elementCount;
            $this->elementCount += $insertedElementCount;
            $this->validateInsertedElements($firstIndex, $insertedElementCount, $firstIndex, $insertedElementCount);

            // Resize the bytemap if the negative first element index is greater than the new element count.
            if (-$originalFirstIndex > $this->elementCount) {
                $overflow = -$originalFirstIndex - $this->elementCount - ($insertedElementCount > 0 ? 0 : 1);
                if ($overflow > 0) {
                    $this->map->insert($insertedElementCount, ...(function () use ($overflow): \Generator {
                        do {
                            yield $this->defaultValue;
                        } while (--$overflow > 0);
                    })());
                }
            }
            $this->elementCount = \count($this->map);
        }
    }

    public function streamJson($stream): void
    {
        self::ensureStream($stream);

        $defaultValue = $this->defaultValue;

        $buffer = '[';
        for ($i = 0, $penultimate = $this->elementCount - 1; $i < $penultimate; ++$i) {
            $buffer .= \json_encode($this->map[$i]).',';
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

        $bytemap = new static($defaultValue);
        if (self::hasStreamingParser()) {
            $maxKey = -1;
            $listener = new BytemapListener(function (?int $key, string $value) use ($bytemap, &$maxKey): void {
                if (null === $key) {
                    $bytemap[] = $value;
                } else {
                    $unassignedCount = $key - $maxKey - 1;
                    if ($unassignedCount < 0) {
                        $bytemap[$key] = $value;
                    } else {
                        if ($unassignedCount > 0) {
                            $bytemap->map->allocate($maxKey + 1);
                            for ($i = 0; $i < $unassignedCount; ++$i) {
                                $bytemap[] = $bytemap->defaultValue;
                            }
                        }
                        $bytemap[] = $value;
                        $maxKey = $key;
                    }
                }
            });
            self::parseJsonStreamOnline($jsonStream, $listener);
        } else {
            $map = self::parseJsonStreamNatively($jsonStream);
            [$maxKey, $sorted] = self::validateMapAndGetMaxKey($map, $defaultValue);
            $size = \count($map);
            if ($size > 0) {
                $bytemap->map->allocate($maxKey + 1);
                if (!$sorted) {
                    \ksort($map, \SORT_NUMERIC);
                }
                if ($maxKey + 1 === $size) {
                    $bytemap->map->push(...$map);
                } else {
                    $lastIndex = -1;
                    foreach ($map as $key => $value) {
                        for ($i = $lastIndex + 1; $i < $key; ++$i) {
                            $bytemap[$i] = $defaultValue;
                        }
                        $bytemap[$lastIndex = $key] = $value;
                    }
                }
                $bytemap->deriveProperties();
            }
        }

        return $bytemap;
    }

    // `AbstractBytemap`
    protected function deleteWithNonNegativeIndex(int $firstIndex, int $howMany, int $elementCount): void
    {
        $maximumRange = $elementCount - $firstIndex;
        if ($howMany >= $maximumRange) {
            $this->elementCount -= $maximumRange;
            while (--$maximumRange >= 0) {
                $this->map->pop();
            }
        } else {
            $this->elementCount -= $howMany;
            if (0 === $firstIndex) {
                $this->deleteFromHead($howMany);
            } else {
                $this->map = $this->map->slice(0, $firstIndex)->merge($this->map->slice($firstIndex + $howMany));
            }
        }
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
        $map = clone $this->map;
        if ($howManyToReturn > 0) {
            if ($whitelist) {
                for ($i = $howManyToSkip, $elementCount = $this->elementCount; $i < $elementCount; ++$i) {
                    if (isset($elements[$element = $map[$i]])) {
                        yield $i => $element;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            } else {
                for ($i = $howManyToSkip, $elementCount = $this->elementCount; $i < $elementCount; ++$i) {
                    if (!isset($elements[$element = $map[$i]])) {
                        yield $i => $element;
                        if (0 === --$howManyToReturn) {
                            return;
                        }
                    }
                }
            }
        } elseif ($whitelist) {
            for ($i = $this->elementCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (isset($elements[$element = $map[$i]])) {
                    yield $i => $element;
                    if (0 === ++$howManyToReturn) {
                        return;
                    }
                }
            }
        } else {
            for ($i = $this->elementCount - 1 - $howManyToSkip; $i >= 0; --$i) {
                if (!isset($elements[$element = $map[$i]])) {
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

        $clone = clone $this;
        if ($howManyToReturn > 0) {
            for ($i = $howManyToSkip, $elementCount = $this->elementCount; $i < $elementCount; ++$i) {
                if (!($whitelist xor $lookup[$element = $clone->map[$i]] ?? ($match = (null !== \preg_filter($patterns, '', $element))))) {
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
                if (!($whitelist xor $lookup[$element = $clone->map[$i]] ?? ($match = (null !== \preg_filter($patterns, '', $element))))) {
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
        $this->validateUnserializedMap();

        $bytesPerElement = \strlen($this->defaultValue);
        foreach ($this->map as $element) {
            if (!\is_string($element)) {
                throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be of type string, '.\gettype($element).' given)');
            }
            if (\strlen($element) !== $bytesPerElement) {
                throw new \DomainException(self::EXCEPTION_PREFIX.'Failed to unserialize (value must be exactly '.$bytesPerElement.' bytes, '.\strlen($element).' given)');
            }
        }
    }

    // `AbstractDsBytemap`
    protected function deleteFromHead(int $howMany): void
    {
        $this->map = $this->map->slice($howMany);
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

    abstract protected function validateUnserializedMap(): void;
}

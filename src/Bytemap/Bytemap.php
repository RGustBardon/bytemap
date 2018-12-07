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

/**
 * An implementation of the `BytemapInterface` using a string.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
class Bytemap extends AbstractBytemap
{
    protected const BATCH_ELEMENT_COUNT = 256;

    /** @var int */
    private $bytesInTotal;
    /** @var bool */
    private $singleByte;

    // `ArrayAccess`
    public function offsetGet($index): string
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            if ($this->singleByte) {
                return $this->map[$index];
            }

            return \substr($this->map, $index * $this->bytesPerElement, $this->bytesPerElement);
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
            $bytesPerElement = $this->bytesPerElement;
            if ($unassignedCount < 0) {
                // Case 1. Overwrite an existing element.
                if ($this->singleByte) {
                    $this->map[$index] = $element;
                } else {
                    $elementIndex = 0;
                    $index *= $bytesPerElement;
                    do {
                        $this->map[$index++] = $element[$elementIndex++];
                    } while ($elementIndex < $bytesPerElement);
                }
            } elseif (0 === $unassignedCount) {
                // Case 2. Append an element right after the last one.
                $this->map .= $element;
                ++$this->elementCount;
                $this->bytesInTotal += $bytesPerElement;
            } else {
                // Case 3. Append to a gap after the last element. Fill the gap with default values.
                $this->map .= \str_repeat($this->defaultValue, $unassignedCount).$element;
                $this->elementCount += $unassignedCount + 1;
                $this->bytesInTotal = $this->elementCount * $bytesPerElement;
            }
        } else {
            self::throwOnOffsetSet($index, $element, $this->bytesPerElement);
        }
    }

    public function offsetUnset($index): void
    {
        if (\is_int($index) && $index >= 0 && $index < $this->elementCount) {
            --$this->elementCount;
            $this->bytesInTotal -= $this->bytesPerElement;
            $this->map = \substr_replace($this->map, '', $index * $this->bytesPerElement, $this->bytesPerElement);
        }
    }

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        $elementCount = $this->elementCount;
        $map = $this->map;
        if ($this->singleByte) {
            for ($i = 0; $i < $elementCount; ++$i) {
                yield $i => $map[$i];
            }
        } else {
            $bytesPerElement = $this->bytesPerElement;
            $batchSize = self::BATCH_ELEMENT_COUNT * $bytesPerElement;
            for ($index = 0; $index < $elementCount; $index += self::BATCH_ELEMENT_COUNT) {
                yield from \array_combine(
                    \range($index, \min($elementCount, $index + self::BATCH_ELEMENT_COUNT) - 1),
                    (array) \str_split(\substr($map, $index * $bytesPerElement, $batchSize), $bytesPerElement)
                );
            }
        }
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        if (0 === $this->elementCount) {
            return [];
        }

        $data = \str_split($this->map, $this->bytesPerElement);

        // @codeCoverageIgnoreStart
        if (false === $data) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.'\\str_split returned false when serializing to JSON');
        }
        // @codeCoverageIgnoreEnd

        return $data;
    }

    // `BytemapInterface`
    public function insert(iterable $elements, int $firstIndex = -1): void
    {
        $substring = '';

        $bytesPerElement = $this->bytesPerElement;
        foreach ($elements as $element) {
            if (\is_string($element) && \strlen($element) === $bytesPerElement) {
                $substring .= $element;
            } elseif (\is_string($element)) {
                throw new \DomainException(self::EXCEPTION_PREFIX.'Value must be exactly '.$bytesPerElement.' bytes, '.\strlen($element).' given');
            } else {
                throw new \TypeError(self::EXCEPTION_PREFIX.'Value must be of type string, '.\gettype($element).' given');
            }
        }

        if (-1 === $firstIndex || $firstIndex > $this->elementCount - 1) {
            // Insert the elements.
            $padLength = \strlen($substring) + \max(0, $firstIndex - $this->elementCount) * $this->bytesPerElement;
            $this->map .= \str_pad($substring, $padLength, $this->defaultValue, \STR_PAD_LEFT);
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

            // Resize the bytemap if the negative first element index is greater than the new element count.
            $insertedElementCount = (int) (\strlen($substring) / $this->bytesPerElement);
            $newElementCount = $this->elementCount + $insertedElementCount;
            if (-$originalFirstIndex > $newElementCount) {
                $overflow = -$originalFirstIndex - $newElementCount - ($insertedElementCount > 0 ? 0 : 1);
                $padLength = ($overflow + $insertedElementCount) * $this->bytesPerElement;
                $substring = \str_pad($substring, $padLength, $this->defaultValue, \STR_PAD_RIGHT);
            }

            // Insert the elements.
            $this->map = \substr_replace($this->map, $substring, $firstIndex * $this->bytesPerElement, 0);
        }

        $this->deriveProperties();
    }

    public function streamJson($stream): void
    {
        self::ensureStream($stream);

        $bytesPerElement = $this->bytesPerElement;
        $buffer = '[';
        $index = 0;
        $map = $this->map;

        $batchSize = self::BATCH_ELEMENT_COUNT * $bytesPerElement;
        $bytesInTotalExceptLast = $this->bytesInTotal - $bytesPerElement;
        for ($index = 0; $index < $this->elementCount - 1; $index += self::BATCH_ELEMENT_COUNT) {
            $start = $index * $bytesPerElement;
            $length = \min($batchSize, $bytesInTotalExceptLast - $start);
            $slice = (array) \str_split(\substr($map, $start, $length), $bytesPerElement);
            $buffer .= \implode(',', \array_map('\\json_encode', $slice)).',';
            if (\strlen($buffer) > self::STREAM_BUFFER_SIZE) {
                self::stream($stream, $buffer);
                $buffer = '';
            }
        }
        $buffer .= ($this->elementCount > 0 ? \json_encode(\substr($map, -$bytesPerElement)) : '').']';
        self::stream($stream, $buffer);
    }

    public static function parseJsonStream($jsonStream, $defaultValue): BytemapInterface
    {
        self::ensureStream($jsonStream);

        $bytemap = new self($defaultValue);
        if (self::hasStreamingParser()) {
            self::parseJsonStreamOnline($jsonStream, new BytemapListener([$bytemap, 'offsetSet']));
        } else {
            $map = self::parseJsonStreamNatively($jsonStream);
            [$maxKey, $sorted] = self::validateMapAndGetMaxKey($map, $defaultValue);
            $size = \count($map);
            if ($size > 0) {
                if ($maxKey + 1 === $size) {
                    if (!$sorted) {
                        \ksort($map, \SORT_NUMERIC);
                    }
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

    protected function deleteWithNonNegativeIndex(int $firstIndex, int $howMany, int $elementCount): void
    {
        $maximumRange = $elementCount - $firstIndex;
        if ($howMany >= $maximumRange) {
            $this->map = \substr($this->map, 0, $firstIndex * $this->bytesPerElement);
        } else {
            $this->map = \substr_replace($this->map, '', $firstIndex * $this->bytesPerElement, $howMany * $this->bytesPerElement);
        }

        $this->deriveProperties();
    }

    protected function deriveProperties(): void
    {
        parent::deriveProperties();

        $this->singleByte = 1 === $this->bytesPerElement;
        $this->bytesInTotal = \strlen($this->map);
        $this->elementCount = $this->bytesInTotal / $this->bytesPerElement;
    }

    protected function findArrayElements(
        array $elements,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        if ($this->singleByte) {
            yield from $this->findBytes($elements, $whitelist, $howManyToReturn, $howManyToSkip);
        } else {
            yield from $this->findSubstrings($elements, $whitelist, $howManyToReturn, $howManyToSkip);
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

        $bytesPerElement = $this->bytesPerElement;
        $elementCount = $this->elementCount;
        $map = $this->map;
        if ($howManyToReturn > 0) {
            $batchSize = self::BATCH_ELEMENT_COUNT * $bytesPerElement;
            for ($index = $howManyToSkip; $index < $elementCount; $index += self::BATCH_ELEMENT_COUNT) {
                foreach ((array) \str_split(\substr($map, $index * $bytesPerElement, $batchSize), $bytesPerElement) as $i => $element) {
                    if (!($whitelist xor $lookup[$element] ?? ($match = (null !== \preg_filter($patterns, '', $element))))) {
                        yield $index + $i => $element;
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
            }
        } else {
            for ($index = $this->elementCount - $howManyToSkip - 1; $index >= 0; $index -= self::BATCH_ELEMENT_COUNT) {
                $start = $index - self::BATCH_ELEMENT_COUNT + 1;
                $batchElementCount = self::BATCH_ELEMENT_COUNT + \min(0, $start);
                $start = \max(0, $start);
                $batch = (array) \str_split(\substr($map, $start * $bytesPerElement, $batchElementCount * $bytesPerElement), $bytesPerElement);
                for ($i = $batchElementCount - 1; $i >= 0; --$i) {
                    if (!($whitelist xor $lookup[$element = $batch[$i]] ?? ($match = (null !== \preg_filter($patterns, '', $element))))) {
                        yield $start + $i => $element;
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
    }

    protected function validateUnserializedElements(): void
    {
        if (!\is_string($this->map)) {
            $reason = 'Failed to unserialize (the internal representation of a bytemap must be of type string, '.\gettype($this->map).' given)';

            throw new \TypeError(self::EXCEPTION_PREFIX.$reason);
        }
        if (0 !== \strlen($this->map) % \strlen($this->defaultValue)) {
            $reason = 'Failed to unserialize (the length of the internal string, '.\strlen($this->map).', is not a multiple of the length of the default value, '.\strlen($this->defaultValue).')';

            throw new \DomainException(self::EXCEPTION_PREFIX.$reason);
        }
    }

    // `Bytemap`
    protected function findBytes(
        array $elements,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        // \strspn and \strcspn are not faster as of PHP 7.1.18.

        $map = $this->map;
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

    protected function findSubstrings(
        array $elements,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator {
        $bytesPerElement = $this->bytesPerElement;
        $map = $this->map;
        if ($howManyToReturn > 0) {
            $batchSize = self::BATCH_ELEMENT_COUNT * $bytesPerElement;
            $elementCount = $this->elementCount;
            if ($whitelist) {
                for ($index = $howManyToSkip; $index < $elementCount; $index += self::BATCH_ELEMENT_COUNT) {
                    $batch = (array) \str_split(\substr($map, $index * $bytesPerElement, $batchSize), $bytesPerElement);
                    foreach ($batch as $i => $element) {
                        if (isset($elements[$element])) {
                            yield $index + $i => $element;
                            if (0 === --$howManyToReturn) {
                                return;
                            }
                        }
                    }
                }
            } else {
                for ($index = $howManyToSkip; $index < $elementCount; $index += self::BATCH_ELEMENT_COUNT) {
                    $batch = (array) \str_split(\substr($map, $index * $bytesPerElement, $batchSize), $bytesPerElement);
                    foreach ($batch as $i => $element) {
                        if (!isset($elements[$element])) {
                            yield $index + $i => $element;
                            if (0 === --$howManyToReturn) {
                                return;
                            }
                        }
                    }
                }
            }
        } elseif ($whitelist) {
            for ($index = $this->elementCount - $howManyToSkip - 1; $index >= 0; $index -= self::BATCH_ELEMENT_COUNT) {
                $start = $index - self::BATCH_ELEMENT_COUNT + 1;
                $batchElementCount = self::BATCH_ELEMENT_COUNT + \min(0, $start);
                $start = \max(0, $start);
                $batch = (array) \str_split(\substr($map, $start * $bytesPerElement, $batchElementCount * $bytesPerElement), $bytesPerElement);
                for ($i = $batchElementCount - 1; $i >= 0; --$i) {
                    if (isset($elements[$element = $batch[$i]])) {
                        yield $start + $i => $element;
                        if (0 === ++$howManyToReturn) {
                            return;
                        }
                    }
                }
            }
        } else {
            for ($index = $this->elementCount - $howManyToSkip - 1; $index >= 0; $index -= self::BATCH_ELEMENT_COUNT) {
                $start = $index - self::BATCH_ELEMENT_COUNT + 1;
                $batchElementCount = self::BATCH_ELEMENT_COUNT + \min(0, $start);
                $start = \max(0, $start);
                $batch = (array) \str_split(\substr($map, $start * $bytesPerElement, $batchElementCount * $bytesPerElement), $bytesPerElement);
                for ($i = $batchElementCount - 1; $i >= 0; --$i) {
                    if (!isset($elements[$element = $batch[$i]])) {
                        yield $start + $i => $element;
                        if (0 === ++$howManyToReturn) {
                            return;
                        }
                    }
                }
            }
        }
    }
}

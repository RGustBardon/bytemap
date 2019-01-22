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

use JsonStreamingParser\Exception\ParsingException;
use JsonStreamingParser\Listener\ListenerInterface;
use JsonStreamingParser\Parser;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
abstract class AbstractBytemap implements BytemapInterface
{
    protected const EXCEPTION_PREFIX = 'Bytemap: ';
    protected const GREP_MAXIMUM_LOOKUP_SIZE = 1024;
    protected const STREAM_BUFFER_SIZE = 16384;
    protected const UNSERIALIZED_CLASSES = false;

    protected /* string */ $defaultValue;
    /** @var int */
    protected /* int */ $bytesPerElement;

    /** @var int */
    protected /* int */ $elementCount = 0;
    protected $map;

    /**
     * Creates a new bytemap.
     *
     * @param string $defaultValue the default value has two purposes: it determines the length of
     *                             every element of the bytemap (as it has to have the same length),
     *                             and it is used to fill the gap between the element with the
     *                             highest index and the element being inserted
     *
     * @throws \DomainException if the default value is an empty string
     */
    public function __construct(string $defaultValue)
    {
        if ('' === $defaultValue) {
            throw new \DomainException(self::EXCEPTION_PREFIX.'The default value cannot be an empty string');
        }

        $this->defaultValue = $defaultValue;
        $this->createEmptyMap();

        $this->deriveProperties();
    }

    // `ArrayAccess`
    public function offsetExists($index): bool
    {
        return \is_int($index) && $index >= 0 && $index < $this->elementCount;
    }

    // `Countable`
    public function count(): int
    {
        return $this->elementCount;
    }

    // `Serializable`
    public function serialize(): string
    {
        return \serialize([$this->defaultValue, $this->map]);
    }

    public function unserialize($serialized)
    {
        $this->unserializeAndValidate($serialized);
        $this->validateUnserializedElements();
        $this->deriveProperties();
    }

    // `BytemapInterface`
    public function find(
        ?iterable $elements = null,
        bool $whitelist = true,
        int $howMany = \PHP_INT_MAX,
        ?int $startAfter = null
        ): \Generator {
        if (0 === $howMany) {
            return;
        }

        $howManyToSkip = $this->calculateHowManyToSkip($howMany > 0, $startAfter);
        if (null === $howManyToSkip) {
            return;
        }

        if (null === $elements) {
            $needles = [$this->defaultValue => true];
            $whitelist = !$whitelist;
        } else {
            $needles = [];
            $bytesPerElement = $this->bytesPerElement;
            foreach ($elements as $value) {
                if (\is_string($value) && \strlen($value) === $bytesPerElement) {
                    $needles[$value] = true;
                }
            }
        }

        if ($whitelist && !$needles) {
            return;
        }

        yield from $this->findArrayElements($needles, $whitelist, $howMany, $howManyToSkip);
    }

    public function grep(
        iterable $patterns,
        bool $whitelist = true,
        int $howMany = \PHP_INT_MAX,
        ?int $startAfter = null
        ): \Generator {
        if (!\is_array($patterns)) {
            $patterns = \iterator_to_array($patterns);
        }
        if (!$patterns) {
            if (!$whitelist) {
                yield from $this->getIterator();
            }

            return;
        }
        $patterns = \array_unique($patterns);

        $errorMessage = null;
        \set_error_handler(static function (int $errno, string $errstr) use (&$errorMessage): void {
            $errorMessage = $errstr;
        });
        if (null === \preg_filter($patterns, '', $this->defaultValue)) {
            $pregLastError = \preg_last_error();
            if (\PREG_NO_ERROR !== $pregLastError) {
                $constants = \get_defined_constants(true)['pcre'];
                $matches = \preg_grep('~^PREG_.*_ERROR$~', \array_keys($constants));
                $constants = \array_flip(\array_intersect_key($constants, \array_flip($matches)));
                $errorName = $constants[$pregLastError] ?? null;
            }
        }
        \restore_error_handler();
        if (isset($errorMessage)) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.($errorName ?? 'Grep failed').' ('.$errorMessage.')');
        }

        if (0 === $howMany) {
            return;
        }

        $howManyToSkip = $this->calculateHowManyToSkip($howMany > 0, $startAfter);
        if (null === $howManyToSkip) {
            return;
        }

        $patterns = \preg_filter('~$~', 'S', $patterns);
        if ($this->bytesPerElement > 1) {
            yield from $this->grepMultibyte($patterns, $whitelist, $howMany, $howManyToSkip);
        } else {
            $whitelistNeedles = [];
            $blacklistNeedles = [];
            for ($i = 0; $i < 256; ++$i) {
                $needle = \chr($i);
                if ($whitelist xor null !== \preg_filter($patterns, '', $needle)) {
                    $blacklistNeedles[$needle] = true;
                } else {
                    $whitelistNeedles[$needle] = true;
                }
            }
            $whitelist = \count($whitelistNeedles) <= 128;
            yield from $this->findArrayElements($whitelist ? $whitelistNeedles : $blacklistNeedles, $whitelist, $howMany, $howManyToSkip);
        }
    }

    public function delete(int $firstIndex = -1, int $howMany = \PHP_INT_MAX): void
    {
        $elementCount = $this->elementCount;

        // Calculate the positive index corresponding to the negative one.
        if ($firstIndex < 0) {
            $firstIndex += $elementCount;
        }

        // If we still end up with a negative index, decrease `$howMany`.
        if ($firstIndex < 0) {
            $howMany += $firstIndex;
            $firstIndex = 0;
        }

        // Check if there is anything to delete or if the positive index is out of bounds.
        if ($howMany < 1 || 0 === $elementCount || $firstIndex >= $elementCount) {
            return;
        }

        // Delete the elements.
        $this->deleteWithNonNegativeIndex($firstIndex, $howMany, $elementCount);
    }

    // `AbstractBytemap`
    final protected function calculateHowManyToSkip(bool $searchForwards, ?int $startAfter): ?int
    {
        if (null === $startAfter) {
            return 0;
        }

        if ($startAfter < 0) {
            $startAfter += $this->elementCount;
        }

        if ($searchForwards) {
            return $startAfter < $this->elementCount - 1 ? \max(0, $startAfter + 1) : null;
        }

        return $startAfter > 0 ? \max(0, $this->elementCount - $startAfter) : null;
    }

    final protected function calculateNewSize(iterable $additionalElements, int $firstIndex = -1): ?int
    {
        // Assume that no gap exists between the tail of the bytemap and `$firstIndex`.

        if (\is_array($additionalElements) || $additionalElements instanceof \Countable) {
            return $this->elementCount + \count($additionalElements);
        }

        return null;
    }

    protected function deriveProperties(): void
    {
        $this->bytesPerElement = \strlen($this->defaultValue);
    }

    final protected function throwOnOffsetGet($index): void
    {
        if (!\is_int($index)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type int, '.\gettype($index).' given');
        }

        if (0 === $this->elementCount) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'The container is empty, so index '.$index.' does not exist');
        }

        throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Index out of range: '.$index.', expected 0 <= x <= '.($this->elementCount - 1));
    }

    protected function unserializeAndValidate(string $serialized): void
    {
        $errorMessage = 'details unavailable';
        \set_error_handler(static function (int $errno, string $errstr) use (&$errorMessage): void {
            $errorMessage = $errstr;
        });
        $result = \unserialize($serialized, ['allowed_classes' => static::UNSERIALIZED_CLASSES]);
        \restore_error_handler();

        if (false === $result) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.'Failed to unserialize ('.$errorMessage.')');
        }
        if (!\is_array($result) || !\in_array(\array_keys($result), [[0, 1], [1, 0]], true)) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.'Failed to unserialize (expected an array of two elements)');
        }

        [$defaultValue, $this->map] = $result;

        if (!\is_string($defaultValue)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (the default value must be of type string, '.\gettype($defaultValue).' given)');
        }
        if ('' === $defaultValue) {
            throw new \DomainException(self::EXCEPTION_PREFIX.'Failed to unserialize (the default value cannot be an empty string)');
        }

        $this->defaultValue = $defaultValue;
    }

    final protected static function calculateGreatestCommonDivisor(int $a, int $b): int
    {
        while (0 !== $b) {
            $tmp = $b;
            $b = $a % $b;
            $a = $tmp;
        }

        return $a;
    }

    /**
     * @param mixed $value
     */
    final protected static function ensureStream($value): void
    {
        if (!\is_resource($value)) {
            $type = \gettype($value);
            if ('unknown type' === $type) {  // PHP older than 7.2.
                // @codeCoverageIgnoreStart
                $type = 'resource (closed)';
                // @codeCoverageIgnoreEnd
            }

            throw new \TypeError(self::EXCEPTION_PREFIX.'Expected an open resource, '.$type.' given');
        }

        $resourceType = \get_resource_type($value);
        if ('stream' !== $resourceType) {
            throw new \InvalidArgumentException(self::EXCEPTION_PREFIX.'Expected a stream, '.$resourceType.' given');
        }
    }

    final protected static function getMaxKey(iterable $map): int
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

    final protected static function hasStreamingParser(): bool
    {
        return ($_ENV['BYTEMAP_STREAMING_PARSER'] ?? true) && \interface_exists('\\JsonStreamingParser\\Listener\\ListenerInterface');
    }

    final protected static function parseJsonStreamNatively($jsonStream)
    {
        $contents = \stream_get_contents($jsonStream);
        if (false === $contents) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(self::EXCEPTION_PREFIX.'Failed to read the stream');
            // @codeCoverageIgnoreEnd
        }

        $result = \json_decode($contents, true);

        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.'Failed to parse JSON ('.\json_last_error_msg().')');
        }

        return $result;
    }

    final protected static function parseJsonStreamOnline($jsonStream, ListenerInterface $listener): void
    {
        try {
            (new Parser($jsonStream, $listener))->parse();
        } catch (ParsingException | \UnexpectedValueException $e) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.'Failed to parse JSON ('.$e->getMessage().')');
        }
    }

    final protected static function stream($stream, string $string): void
    {
        for ($written = 0, $size = \strlen($string); $written < $size; $written += $fwrite) {
            if (false === ($fwrite = \fwrite($stream, \substr($string, (int) $written)))) {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException(self::EXCEPTION_PREFIX.'Failed to write JSON data to a stream');
                // @codeCoverageIgnoreEnd
            }
        }
    }

    protected static function throwOnOffsetSet($index, $element, int $bytesPerElement): void
    {
        if (!\is_int($index)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type integer, '.\gettype($index).' given');
        }

        if ($index < 0) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Negative index: '.$index);
        }

        if (!\is_string($element)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Value must be of type string, '.\gettype($element).' given');
        }

        throw new \DomainException(self::EXCEPTION_PREFIX.'Value must be exactly '.$bytesPerElement.' bytes, '.\strlen($element).' given');
    }

    protected static function validateMapAndGetMaxKey($map, string $defaultValue): array
    {
        if (!\is_array($map)) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.'Invalid JSON (expected an array or an object, '.\gettype($map).' given)');
        }

        $sorted = true;
        $maxKey = -1;
        $bytesPerElement = \strlen($defaultValue);
        foreach ($map as $key => $value) {
            if (\is_int($key) && $key >= 0 && \is_string($value) && \strlen($value) === $bytesPerElement) {
                if ($maxKey < $key) {
                    $maxKey = $key;
                } else {
                    $sorted = false;
                }
            } else {
                self::throwOnOffsetSet($key, $value, $bytesPerElement);
            }
        }

        return [$maxKey, $sorted];
    }

    abstract protected function createEmptyMap(): void;

    abstract protected function deleteWithNonNegativeIndex(int $firstIndex, int $howMany, int $elementCount): void;

    abstract protected function findArrayElements(
        array $elements,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator;

    abstract protected function grepMultibyte(
        array $patterns,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator;

    abstract protected function validateUnserializedElements(): void;
}

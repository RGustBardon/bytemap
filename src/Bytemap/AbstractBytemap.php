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

use JsonStreamingParser\Listener;
use JsonStreamingParser\Parser;
use JsonStreamingParser\ParsingError;

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

    /** @var string */
    protected $defaultItem;
    /** @var int */
    protected $bytesPerItem;

    /** @var int */
    protected $itemCount = 0;
    protected $map;

    public function __construct(string $defaultItem)
    {
        if ('' === $defaultItem) {
            throw new \LengthException(self::EXCEPTION_PREFIX.'The default item cannot be an empty string');
        }

        $this->defaultItem = $defaultItem;
        $this->createEmptyMap();

        $this->deriveProperties();
    }

    // Property overloading.
    final public function __get($name)
    {
        throw new \ErrorException('Undefined property: '.static::class.'::$'.$name);
    }

    final public function __set($name, $value): void
    {
        self::__get($name);
    }

    final public function __isset($name): bool
    {
        self::__get($name);
    }

    final public function __unset($name): void
    {
        self::__get($name);
    }

    // `ArrayAccess`
    final public function offsetExists($offset): bool
    {
        return \is_int($offset) && $offset >= 0 && $offset < $this->itemCount;
    }

    // `Countable`
    final public function count(): int
    {
        return $this->itemCount;
    }

    // `Serializable`
    public function serialize(): string
    {
        return \serialize([$this->defaultItem, $this->map]);
    }

    public function unserialize($serialized)
    {
        $this->unserializeAndValidate($serialized);
        $this->validateUnserializedItems();
        $this->deriveProperties();
    }

    // `BytemapInterface`
    public function find(
        ?iterable $items = null,
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

        if (null === $items) {
            $needles = [$this->defaultItem => true];
            $whitelist = !$whitelist;
        } else {
            $needles = [];
            $bytesPerItem = $this->bytesPerItem;
            foreach ($items as $value) {
                if (\is_string($value) && \strlen($value) === $bytesPerItem) {
                    $needles[$value] = true;
                }
            }
        }

        if ($whitelist && !$needles) {
            return;
        }

        yield from $this->findArrayItems($needles, $whitelist, $howMany, $howManyToSkip);
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

        $errorMessage = 'details unavailable';
        \set_error_handler(function (int $errno, string $errstr) use (&$errorMessage) {
            $errorMessage = $errstr;
        });
        if (null === \preg_filter($patterns, '', $this->defaultItem)) {
            $pregLastError = \preg_last_error();
            if (\PREG_NO_ERROR !== $pregLastError) {
                $constants = \get_defined_constants(true)['pcre'];
                $matches = \preg_grep('~^PREG_.*_ERROR$~', \array_keys($constants));
                $constants = \array_flip(\array_intersect_key($constants, \array_flip($matches)));
                $errorName = $constants[$pregLastError] ?? null;
            }
        }
        \restore_error_handler();
        if (isset($errorName)) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.$errorName.' ('.$errorMessage.')');
        }

        if (0 === $howMany) {
            return;
        }

        $howManyToSkip = $this->calculateHowManyToSkip($howMany > 0, $startAfter);
        if (null === $howManyToSkip) {
            return;
        }

        $patterns = \preg_filter('~$~', 'S', $patterns);
        if ($this->bytesPerItem > 1) {
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
            yield from $this->findArrayItems($whitelist ? $whitelistNeedles : $blacklistNeedles, $whitelist, $howMany, $howManyToSkip);
        }
    }

    final public function delete(int $firstItemOffset = -1, int $howMany = \PHP_INT_MAX): void
    {
        $itemCount = $this->itemCount;

        // Check if there is anything to delete.
        if ($howMany < 1 || 0 === $itemCount) {
            return;
        }

        // Calculate the positive offset corresponding to the negative one.
        if ($firstItemOffset < 0) {
            $firstItemOffset += $itemCount;
        }

        // Delete the items.
        $this->deleteWithNonNegativeOffset(\max(0, $firstItemOffset), $howMany, $itemCount);
    }

    // `AbstractBytemap`
    final protected function calculateHowManyToSkip(bool $searchForwards, ?int $startAfter): ?int
    {
        if (null === $startAfter) {
            return 0;
        }

        if ($startAfter < 0) {
            $startAfter += $this->itemCount;
        }

        if ($searchForwards) {
            return $startAfter < $this->itemCount - 1 ? \max(0, $startAfter + 1) : null;
        }

        return $startAfter > 0 ? \max(0, $this->itemCount - $startAfter) : null;
    }

    final protected function calculateNewSize(iterable $additionalItems, int $firstItemOffset = -1): ?int
    {
        // Assume that no gap exists between the tail of the bytemap and `$firstItemOffset`.

        if (\is_array($additionalItems) || $additionalItems instanceof \Countable) {
            return $this->itemCount + \count($additionalItems);
        }

        return null;
    }

    protected function deriveProperties(): void
    {
        $this->bytesPerItem = \strlen($this->defaultItem);
    }

    final protected function throwOnOffsetGet($offset): void
    {
        if (!\is_int($offset)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type int, '.\gettype($offset).' given');
        }

        if (0 === $this->itemCount) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'The container is empty, so index '.$offset.' does not exist');
        }

        throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Index out of range: '.$offset.', expected 0 <= x <= '.($this->itemCount - 1));
    }

    protected function unserializeAndValidate(string $serialized): void
    {
        $errorMessage = 'details unavailable';
        \set_error_handler(function (int $errno, string $errstr) use (&$errorMessage) {
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

        [$this->defaultItem, $this->map] = $result;

        if (!\is_string($this->defaultItem)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (the default item must be of type string, '.\gettype($this->defaultItem).' given)');
        }
        if ('' === $this->defaultItem) {
            throw new \LengthException(self::EXCEPTION_PREFIX.'Failed to unserialize (the default item cannot be an empty string)');
        }
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
        return ($_ENV['BYTEMAP_STREAMING_PARSER'] ?? true) && \class_exists('\\JsonStreamingParser\\Parser');
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

    final protected static function parseJsonStreamOnline($jsonStream, Listener $listener): void
    {
        try {
            (new Parser($jsonStream, $listener))->parse();
        } catch (ParsingError | \UnexpectedValueException $e) {
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

    protected static function throwOnOffsetSet($offset, $item, int $bytesPerItem): void
    {
        if (!\is_int($offset)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type integer, '.\gettype($offset).' given');
        }

        if ($offset < 0) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Negative index: '.$offset);
        }

        if (!\is_string($item)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Value must be of type string, '.\gettype($item).' given');
        }

        throw new \LengthException(self::EXCEPTION_PREFIX.'Value must be exactly '.$bytesPerItem.' bytes, '.\strlen($item).' given');
    }

    protected static function validateMapAndGetMaxKey($map, string $defaultItem): array
    {
        if (!\is_array($map)) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.'Invalid JSON (expected an array or an object, '.\gettype($map).' given)');
        }

        $sorted = true;
        $maxKey = -1;
        $bytesPerItem = \strlen($defaultItem);
        foreach ($map as $key => $item) {
            if (\is_int($key) && $key >= 0 && \is_string($item) && \strlen($item) === $bytesPerItem) {
                if ($maxKey < $key) {
                    $maxKey = $key;
                } else {
                    $sorted = false;
                }
            } else {
                self::throwOnOffsetSet($key, $item, $bytesPerItem);
            }
        }

        return [$maxKey, $sorted];
    }

    abstract protected function createEmptyMap(): void;

    abstract protected function deleteWithNonNegativeOffset(int $firstItemOffset, int $howMany, int $itemCount): void;

    abstract protected function findArrayItems(
        array $items,
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

    abstract protected function validateUnserializedItems(): void;
}

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
    protected const GREP_MAXIMUM_LOOKUP_SIZE = 1024;
    protected const UNSERIALIZED_CLASSES = false;

    protected $defaultItem;

    /** @var int */
    protected $itemCount = 0;
    protected $map;

    public function __construct(string $defaultItem)
    {
        if ('' === $defaultItem) {
            throw new \LengthException('Bytemap: The default item cannot be an empty string.');
        }

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
        $this->unserializeAndValidate($serialized);
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
            foreach ($items as $value) {
                if (\is_string($value)) {
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
        string $regex,
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

        if (isset($this->defaultItem[1])) {
            yield from $this->grepMultibyte($regex.'S', $whitelist, $howMany, $howManyToSkip);
        } else {
            $whitelistNeedles = [];
            $blacklistNeedles = [];
            for ($i = 0; $i < 256; ++$i) {
                $needle = \chr($i);
                if ($whitelist xor \preg_match($regex, $needle)) {
                    $blacklistNeedles[$needle] = true;
                } else {
                    $whitelistNeedles[$needle] = true;
                }
            }
            $whitelist = \count($whitelistNeedles) <= 128;
            yield from $this->findArrayItems($whitelist ? $whitelistNeedles : $blacklistNeedles, $whitelist, $howMany, $howManyToSkip);
        }
    }

    public function delete(int $firstItemOffset = -1, int $howMany = \PHP_INT_MAX): void
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
        $this->deleteWithPositiveOffset(\max(0, $firstItemOffset), $howMany, $itemCount);
    }

    public function streamJson($stream): void
    {
        self::ensureResource($stream);

        \fwrite($stream, '[');
        for ($i = 0; $i < $this->itemCount - 1; ++$i) {
            \fwrite($stream, \json_encode($this[$i]).',');
        }
        \fwrite($stream, ($this->itemCount > 0 ? \json_encode($this[$i]) : '').']');
    }

    // `AbstractBytemap`
    protected function calculateHowManyToSkip(bool $searchForwards, ?int $startAfter): ?int
    {
        if (null === $startAfter) {
            return 0;
        }

        $itemCount = $this->itemCount;

        if ($startAfter < 0) {
            $startAfter += $itemCount;
        }

        if ($searchForwards) {
            if ($startAfter >= $itemCount - 1) {
                return null;
            }

            return \max(0, $startAfter + 1);
        }

        if ($startAfter <= 0) {
            return null;
        }

        return $startAfter <= 0 ? null : \max(0, $itemCount - $startAfter);
    }

    protected function calculateNewSize(iterable $additionalItems, int $firstItemOffset = -1): ?int
    {
        // Assume that no gap exists between the tail of the bytemap and `$firstItemOffset`.

        if (\is_array($additionalItems) || $additionalItems instanceof \Countable) {
            $insertedItemCount = \count($additionalItems);
            $newSize = $this->itemCount + $insertedItemCount;
            if ($firstItemOffset < -1 && -$firstItemOffset > $this->itemCount) {
                $newSize += -$firstItemOffset - $newSize - ($insertedItemCount > 0 ? 0 : 1);
            }

            return $newSize;
        }

        return null;
    }

    protected function unserializeAndValidate(string $serialized): void
    {
        $result = \unserialize($serialized, ['allowed_classes' => static::UNSERIALIZED_CLASSES]);

        if (false === $result) {
            throw new \UnexpectedValueException('Bytemap: failed to unserialize.');
        }
        if (!\is_array($result) || !\in_array(\array_keys($result), [[0, 1], [1, 0]], true)) {
            throw new \UnexpectedValueException('Bytemap: unserialized data must be an array of two elements.');
        }

        [$this->defaultItem, $this->map] = $result;

        if (!\is_string($this->defaultItem)) {
            throw new \UnexpectedValueException('Bytemap: The default item must be a string.');
        }
        if ('' === $this->defaultItem) {
            throw new \LengthException('Bytemap: The default item cannot be an empty string.');
        }
    }

    protected static function calculateGreatestCommonDivisor(int $a, int $b): int
    {
        while (0 !== $b) {
            $tmp = $b;
            $b = $a % $b;
            $a = $tmp;
        }

        return $a;
    }

    protected static function ensureJsonDecodedSuccessfully(): void
    {
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new \UnexpectedValueException('Bytemap: \\json_decode failed: '.\json_last_error_msg());
        }
    }

    /**
     * @codeCoverageIgnore
     *
     * @param mixed $value
     */
    protected static function ensureResource($value): void
    {
        if (!\is_resource($value)) {
            $type = \gettype($value);
            if ('unknown type' === $type) {
                $type = 'resource (closed)';
            }

            throw new \InvalidArgumentException(
                \sprintf('Bytemap: streaming requires an open resource (%s has been passed instead).', $type)
                );
        }
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

    protected static function hasStreamingParser(): bool
    {
        return ($_ENV['BYTEMAP_STREAMING_PARSER'] ?? true) && \class_exists('\\JsonStreamingParser\\Parser');
    }

    abstract protected function createEmptyMap(): void;

    abstract protected function deleteWithPositiveOffset(int $firstItemOffset, int $howMany, int $itemCount): void;

    abstract protected function deriveProperties(): void;

    abstract protected function findArrayItems(
        array $items,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator;

    abstract protected function grepMultibyte(
        string $regex,
        bool $whitelist,
        int $howManyToReturn,
        int $howManyToSkip
        ): \Generator;
}

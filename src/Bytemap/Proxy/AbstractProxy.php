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

abstract class AbstractProxy implements ArrayProxyInterface
{
    /** @var BytemapInterface */
    protected $bytemap;

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
    public function offsetExists($index): bool
    {
        return isset($this->bytemap[$index]);
    }

    public function offsetGet($index): string
    {
        return $this->bytemap[$index];
    }

    public function offsetSet($index, $element): void
    {
        $this->bytemap[$index] = $element;
    }

    public function offsetUnset($index): void
    {
        unset($this->bytemap[$index]);
    }

    // `Countable`
    public function count(): int
    {
        return $this->bytemap->count();
    }

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        return $this->bytemap->getIterator();
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        return $this->bytemap->jsonSerialize();
    }

    // `Serializable`
    public function serialize(): string
    {
        return \serialize($this->bytemap);
    }

    public function unserialize($serialized)
    {
        $errorMessage = 'details unavailable';
        \set_error_handler(function (int $errno, string $errstr) use (&$errorMessage) {
            $errorMessage = $errstr;
        });
        $result = \unserialize($serialized, ['allowed_classes' => [Bytemap::class]]);
        \restore_error_handler();

        if (false === $result) {
            throw new \UnexpectedValueException(static::class.': Failed to unserialize ('.$errorMessage.')');
        }

        if (!\is_object($result) || !($result instanceof Bytemap)) {
            throw new \UnexpectedValueException(static::class.': Failed to unserialize (expected a bytemap)');
        }

        $this->bytemap = $result;
    }

    // `ProxyInterface`
    public function unwrap(): BytemapInterface
    {
        return $this->bytemap;
    }

    // `AbstractProxy`
    protected function createEmptyBytemap(): BytemapInterface
    {
        $elementCount = \count($this->bytemap);
        if ($elementCount > 0) {
            $this->bytemap[$elementCount + 1] = $this->bytemap[0];
            $clone = new Bytemap($this->bytemap[$elementCount]);
            $this->bytemap->delete($elementCount);

            return $clone;
        }

        return clone $this->bytemap;
    }

    protected static function getComparator(int $sortFlags, bool $ascending = true): callable
    {
        $caseInsensitive = ($sortFlags & \SORT_FLAG_CASE);
        $sortFlags &= ~\SORT_FLAG_CASE;

        if (\SORT_NUMERIC === $sortFlags) {
            if ($ascending) {
                return function (string $a, string $b): int {
                    return (float) $a <=> (float) $b;
                };
            }

            return function (string $a, string $b): int {
                return (float) $b <=> (float) $a;
            };
        }

        if (\defined('\\SORT_LOCALE_STRING') && \is_callable('\\strcoll') && \SORT_LOCALE_STRING === $sortFlags) {
            if ($ascending) {
                return '\\strcoll';
            }

            return function (string $a, string $b): int {
                return \strcoll($b, $a);
            };
        }

        if (\defined('\\SORT_NATURAL') && \SORT_NATURAL === $sortFlags) {
            if ($caseInsensitive) {
                if (\is_callable('\\strnatcasecmp')) {
                    if ($ascending) {
                        return '\\strnatcasecmp';
                    }

                    return function (string $a, string $b): int {
                        return \strnatcasecmp($b, $a);
                    };
                }
            } elseif (\is_callable('\\strnatcmp')) {
                if ($ascending) {
                    return '\\strnatcmp';
                }

                return function (string $a, string $b): int {
                    return \strnatcmp($b, $a);
                };
            }
        }

        if (\SORT_REGULAR === $sortFlags) {
            if ($ascending) {
                return function (string $a, string $b): int {
                    return (\is_numeric($a) ? (string) (float) $a : $a) <=> (\is_numeric($b) ? (string) (float) $b : $b);
                };
            }

            return function (string $a, string $b): int {
                return (\is_numeric($b) ? (string) (float) $b : $b) <=> (\is_numeric($a) ? (string) (float) $a : $a);
            };
        }

        if ($caseInsensitive) {
            if ($ascending) {
                return '\\strcasecmp';
            }

            return function (string $a, string $b): int {
                return \strcasecmp($b, $a);
            };
        }

        if ($ascending) {
            return '\\strcmp';
        }

        return function (string $a, string $b): int {
            return \strcmp($b, $a);
        };
    }

    protected static function sortElements(
        BytemapInterface $bytemap,
        callable $comparator,
        array &$keysToReorder = [],
        array $iterablesToReorder = [],
        array $swappers = []
    ): void {
        // Heapsort.
        $elementCount = \count($bytemap);
        for ($i = (int) ($elementCount / 2) - 1; $i >= 0; --$i) {
            self::heapify($bytemap, $comparator, $keysToReorder, $iterablesToReorder, $swappers, $elementCount, $i);
        }

        for ($i = $elementCount - 1; $i >= 0; --$i) {
            $swapped = $bytemap[0];
            $bytemap[0] = $bytemap[$i];
            $bytemap[$i] = $swapped;

            foreach ($swappers as $index => $swapper) {
                $swapper($iterablesToReorder[$index], 0, $i, $keysToReorder[$index]);
            }

            self::heapify($bytemap, $comparator, $keysToReorder, $iterablesToReorder, $swappers, $i, 0);
        }
    }

    protected static function wrapGenerically(BytemapInterface $bytemap): ProxyInterface
    {
        $class = new \ReflectionClass(static::class);
        /** @var AbstractProxy $proxy */
        $proxy = $class->newInstanceWithoutConstructor();
        $proxy->bytemap = $bytemap;

        return $proxy;
    }

    private static function heapify(
        BytemapInterface $bytemap,
        callable $comparator,
        array &$keysToReorder,
        array $iterablesToReorder,
        array $swappers,
        int $elementCount,
        int $subtreeRoot
    ): void {
        $greatest = $subtreeRoot;

        $left = 2 * $subtreeRoot + 1;
        if ($left < $elementCount && $comparator($bytemap[$left], $bytemap[$greatest]) > 0) {
            $greatest = $left;
        }

        $right = 2 * $subtreeRoot + 2;
        if ($right < $elementCount && $comparator($bytemap[$right], $bytemap[$greatest]) > 0) {
            $greatest = $right;
        }

        if ($greatest !== $subtreeRoot) {
            $swapped = $bytemap[$subtreeRoot];
            $bytemap[$subtreeRoot] = $bytemap[$greatest];
            $bytemap[$greatest] = $swapped;

            foreach ($swappers as $index => $swapper) {
                $swapper($iterablesToReorder[$index], $subtreeRoot, $greatest, $keysToReorder[$index]);
            }

            self::heapify($bytemap, $comparator, $keysToReorder, $iterablesToReorder, $swappers, $elementCount, $greatest);
        }
    }
}

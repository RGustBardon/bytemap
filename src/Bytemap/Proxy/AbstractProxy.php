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

    protected static function getComparator(int $sortFlags, bool $reverse = false): callable
    {
        $caseInsensitive = ($sortFlags & \SORT_FLAG_CASE);
        $sortFlags &= ~\SORT_FLAG_CASE;

        if (\SORT_NUMERIC === $sortFlags) {
            if ($reverse) {
                return function (string $a, string $b): int {
                    return (float) $b <=> (float) $a;
                };
            }

            return function (string $a, string $b): int {
                return (float) $a <=> (float) $b;
            };
        }

        if (\defined('\\SORT_LOCALE_STRING') && \is_callable('\\strcoll') && \SORT_LOCALE_STRING === $sortFlags) {
            if ($reverse) {
                return function (string $a, string $b): int {
                    return \strcoll($b, $a);
                };
            }

            return '\\strcoll';
        }

        if (\defined('\\SORT_NATURAL') && \SORT_NATURAL === $sortFlags) {
            if ($caseInsensitive) {
                if (\is_callable('\\strnatcasecmp')) {
                    if ($reverse) {
                        return function (string $a, string $b): int {
                            return \strnatcasecmp($b, $a);
                        };
                    }

                    return '\\strnatcasecmp';
                }
            } elseif (\is_callable('\\strnatcmp')) {
                if ($reverse) {
                    return function (string $a, string $b): int {
                        return \strnatcmp($b, $a);
                    };
                }

                return '\\strnatcmp';
            }
        }

        if (\SORT_REGULAR === $sortFlags) {
            if ($reverse) {
                return function (string $a, string $b): int {
                    return (\is_numeric($b) ? (string) (float) $b : $b) <=> (\is_numeric($a) ? (string) (float) $a : $a);
                };
            }

            return function (string $a, string $b): int {
                return (\is_numeric($a) ? (string) (float) $a : $a) <=> (\is_numeric($b) ? (string) (float) $b : $b);
            };
        }

        if ($caseInsensitive) {
            if ($reverse) {
                return function (string $a, string $b): int {
                    return \strcasecmp($b, $a);
                };
            }

            return '\\strcasecmp';
        }
        if ($reverse) {
            return function (string $a, string $b): int {
                return \strcmp($b, $a);
            };
        }

        return '\\strcmp';
    }

    protected static function sortBytemapByElement(BytemapInterface $bytemap, callable $comparator): BytemapInterface
    {
        // Quicksort.
        $elementCount = \count($bytemap);
        if ($elementCount > 1) {
            foreach ($bytemap as $element) {
                if (isset($pivot)) {
                    if ((int) $comparator($element, $pivot) < 0) {
                        $left[] = $element;
                    } else {
                        $right[] = $element;
                    }
                } else {
                    $pivot = $bytemap[0];
                    $left = new Bytemap($pivot);
                    $right = clone $left;
                }
            }

            $bytemap->delete(0);
            if (isset($left) && $left instanceof BytemapInterface) {
                $bytemap->insert(self::sortBytemapByElement($left, $comparator));
            }
            if (isset($pivot)) {
                $bytemap[] = $pivot;
            }
            if (isset($right) && $right instanceof BytemapInterface) {
                $bytemap->insert(self::sortBytemapByElement($right, $comparator));
            }
        }

        return $bytemap;
    }

    protected static function sortBytemapByElementAndReorder(
        BytemapInterface $bytemap,
        callable $comparator,
        array $swappers,
        &...$iterables
    ): BytemapInterface {
        // TODO: Implement.

        return $bytemap;
    }

    protected static function wrapGenerically(BytemapInterface $bytemap): ProxyInterface
    {
        $class = new \ReflectionClass(static::class);
        /** @var AbstractProxy $proxy */
        $proxy = $class->newInstanceWithoutConstructor();
        $proxy->bytemap = $bytemap;

        return $proxy;
    }

    private static function heapify(BytemapInterface $bytemap, callable $comparator, $swappers, &...$iterables): void
    {
    }
}

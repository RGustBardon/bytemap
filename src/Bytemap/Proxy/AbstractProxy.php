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
    public function offsetExists($offset): bool
    {
        return isset($this->bytemap[$offset]);
    }

    public function offsetGet($offset): string
    {
        return $this->bytemap[$offset];
    }

    public function offsetSet($offset, $item): void
    {
        $this->bytemap[$offset] = $item;
    }

    public function offsetUnset($offset): void
    {
        unset($this->bytemap[$offset]);
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

    protected static function sortBytemapByItem(BytemapInterface $bytemap, callable $comparator): BytemapInterface
    {
        // Quicksort.
        $itemCount = \count($bytemap);
        if ($itemCount > 1) {
            foreach ($bytemap as $item) {
                if (isset($pivot)) {
                    if ($comparator($item, $pivot) < 0) {
                        $left[] = $item;
                    } else {
                        $right[] = $item;
                    }
                } else {
                    $pivot = $bytemap[0];
                    $left = new Bytemap($pivot);
                    $right = clone $left;
                }
            }

            $bytemap->delete(0);
            $bytemap->insert(self::sortBytemapByItem($left, $comparator));
            $bytemap[] = $pivot;
            $bytemap->insert(self::sortBytemapByItem($right, $comparator));
        }

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
}

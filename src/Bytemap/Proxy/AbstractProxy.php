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
        return \json_encode($this->bytemap);
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
        $result = \unserialize($serialized, ['allowed_classes' => Bytemap::class]);
        \restore_error_handler();

        if (false === $result) {
            throw new \UnexpectedValueException(static::class.': Failed to unserialize ('.$errorMessage.')');
        }
    }

    // `ProxyInterface`
    public function unwrap(): BytemapInterface
    {
        return $this->bytemap;
    }

    // `AbstractProxy`
    protected static function wrapGenerically(BytemapInterface $bytemap): ProxyInterface
    {
        $class = new \ReflectionClass(static::class);
        $proxy = $class->newInstanceWithoutConstructor();
        $proxy->bytemap = $bytemap;

        return $proxy;
    }
}

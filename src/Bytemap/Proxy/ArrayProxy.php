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

class ArrayProxy extends AbstractProxy implements ArrayProxyInterface
{
    public function __construct(string $defaultItem, string ...$values)
    {
        $this->bytemap = new Bytemap($defaultItem);
        $this->bytemap->insert($values);
    }

    // `ProxyInterface`
    public function unwrap(): BytemapInterface
    {
        return $this->bytemap;
    }

    // `ArrayProxyInterface`: Bytemap encapsulation
    public static function wrap(BytemapInterface $bytemap): ArrayProxyInterface
    {
        /** @var ArrayProxyInterface $arrayProxy */
        $arrayProxy = self::wrapGenerically($bytemap);
        \count($arrayProxy);  // PHP CS Fixer.

        return $arrayProxy;
    }

    // `ArrayProxyInterface`: Array conversion
    public function exportArray(): array
    {
        return $this->jsonSerialize();
    }

    public static function importArray(string $defaultItem, array $array): ArrayProxyInterface
    {
        return new self($defaultItem, ...$array);
    }

    // `ArrayProxyInterface`: Array API
    public function keyExists($key): bool
    {
        if (!\is_int($key)) {
            if (!\is_string($key)) {
                throw new \TypeError('The argument should be either a string or an integer');
            }

            if ((string) (int) $key !== $key) {
                return false;
            }
        }

        return $key >= 0 && $key < \count($this->bytemap);
    }

    public function keyFirst(): ?int
    {
        return \count($this->bytemap) > 0 ? 0 : null;
    }

    public function keyLast(): ?int
    {
        $itemCount = \count($this->bytemap);
        return $itemCount > 0 ? $itemCount - 1 : null;
    }

    public function keys($searchValue = null, bool $strict = false): array
    {
        if (null === $searchValue) {
            $itemCount = \count($this->bytemap);
            return $itemCount > 0 ? range(0, $itemCount - 1) : [];
        }

        if ($strict && !\is_string($searchValue)) {
            return [];
        }

        $keys = [];
        foreach ($this->bytemap->find([(string) $searchValue]) as $key => $value) {
            $keys[] = $key;
        }
        return $keys;
    }

    public static function fill(string $defaultItem, int $startIndex, int $num, ?string $value = null): ArrayProxyInterface
    {
        if ($startIndex < 0) {
            throw new \OutOfRangeException('Start index can\'t be negative');
        }

        if ($num < 0) {
            throw new \OutOfRangeException('Number of elements can\'t be negative');
        }

        if (null === $value) {
            $value = $defaultItem;
        }

        $arrayProxy = new self($defaultItem);
        $arrayProxy->bytemap->insert((function () use ($num, $value): \Generator {
            for ($i = 0; $i < $num; ++$i) {
                yield $value;
            }
        })(), $startIndex);

        return $arrayProxy;
    }
}

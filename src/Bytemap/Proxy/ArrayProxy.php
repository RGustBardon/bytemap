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

class ArrayProxy extends AbstractProxy
{
    public function __construct(string $defaultItem, string ...$values)
    {
        $this->bytemap = new Bytemap($defaultItem);
        $this->bytemap->insert($values);
    }

    // `ArrayProxyInterface`
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

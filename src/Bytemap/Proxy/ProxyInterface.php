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

use Bytemap\BytemapInterface;

interface ProxyInterface extends \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, \Serializable
{
    /**
     * @param string   $defaultItem
     * @param iterable $items
     */
    public static function import(string $defaultItem, iterable $items);

    /**
     * @return BytemapInterface
     */
    public function unwrap(): BytemapInterface;

    /**
     * @param BytemapInterface $bytemap
     */
    public static function wrap(BytemapInterface $bytemap);
}

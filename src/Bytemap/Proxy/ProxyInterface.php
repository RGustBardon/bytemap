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
     * Instantiates a bytemap and a proxy to it.
     *
     * @param string   $defaultItem the default item of the underyling bytemap that is to be
     *                              constructed
     * @param iterable $items       the items that are to be inserted into the bytemap
     *
     * @return self a proxy to a bytemap that has been constructed based on the arguments
     */
    public static function import(string $defaultItem, iterable $items);

    /**
     * Returns the bytemap the proxy has been acting for.
     *
     * @return BytemapInterface the underlying bytemap
     */
    public function unwrap(): BytemapInterface;

    /**
     * Instantiates a proxy to a certain bytemap.
     *
     * @param BytemapInterface $bytemap a bytemap that the proxy is to act for
     *
     * @return self a proxy to the bytemap
     */
    public static function wrap(BytemapInterface $bytemap);
}

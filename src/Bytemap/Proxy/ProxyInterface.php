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

/**
 * A proxy which enables interaction with a bytemap using the API of another data structure.
 *
 * A proxy is not a drop-in replacement. It merely serves demonstrational purposes. The APIs of
 * other data structures have been modified. For instance, when it comes to the API of native PHP
 * arrays, the corresponding proxy does not feature an equivalent of the `\krsort` function as the
 * indices of a bytemap must be sorted in the ascending order. `array_chunk` has become
 * `ArrayProxyInterface::chunk` with its first parameter (`$array`) removed (since any instance of
 * that proxy already contains a bytemap). Moreover, the method generates chunks instead of
 * returning their array (to make it more generic and require less memory) and throws an exception
 * instead of emitting a warning if `$size` is less than one.
 *
 * Unless indicated otherwise, when the bytemap is iterated, the iteration takes place from the
 * element with index `0` to the element with the greatest index.
 */
interface ProxyInterface extends \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, \Serializable
{
    /**
     * Instantiates a bytemap and a proxy to it.
     *
     * @param string   $defaultValue the default value of the underlying bytemap that is to be
     *                               constructed
     * @param iterable $elements     the elements that are to be inserted into the bytemap
     *
     * @return self a proxy to a bytemap that has been constructed based on the arguments
     */
    public static function import(string $defaultValue, iterable $elements);

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

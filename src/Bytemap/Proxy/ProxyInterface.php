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
 *
 * In addition to the exceptions thrown by the documented methods,
 * the following exceptions may be thrown:
 * - `\ErrorException` (when referring to undefined properties)
 *   thrown by `__isset`, `__get`, `__set`, `__unset`;
 * - `\TypeError` (when attempting to use a value other than an integer as an index)
 *   thrown by `offsetGet`, `offsetSet`;
 * - `\OutOfRangeException` (when attempting to use a negative integer as an index)
 *   thrown by `offsetGet`, `offsetSet`;
 * - `\TypeError` (when the type of a value is not the one of the data domain)
 *   thrown by `offsetSet`, `unserialize`;
 * - `\DomainException` (when a value lies outside of the data domain despite its type)
 *   thrown by `offsetSet`, `unserialize`;
 * - `\UnexpectedValueException` (when it is not possible to unserialize a value)
 *   thrown by `unserialize`;
 * - `\UnexpectedValueException` (when an unserialized value has an unexpected structure)
 *   thrown by `unserialize`.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
interface ProxyInterface extends \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, \Serializable
{
    /**
     * Instantiates a bytemap and a proxy to it.
     *
     * @param string   $defaultValue the default value of the underlying bytemap that is to be
     *                               constructed
     * @param iterable $elements     the elements that are to be inserted into the bytemap (keys are
     *                               ignored)
     *
     * @throws \DomainException if the default value is an empty string
     * @throws \TypeError       if any element that is to be inserted is not of the expected type
     * @throws \DomainException if any element that is to be inserted is of the expected type,
     *                          but does not belong to the data domain of the bytemap
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

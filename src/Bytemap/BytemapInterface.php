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

namespace Bytemap;

/**
 * A vector of values which belong to the same well-defined data domain.
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
interface BytemapInterface extends \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, \Serializable
{
    /**
     * Returns the items that match a certain whitelist or a certain blacklist.
     *
     * Values are compared strictly.
     *
     * @param ?iterable $items      the items to look for (whitelist) or to ignore (blacklist).
     *                              `null` means all the items except for the default one
     * @param bool      $whitelist  `true` if the first argument is a whitelist
     * @param int       $howMany    The maximum number of matches. By default, all the matches are included.
     *                              If negative, the search starts from the end.
     * @param ?int      $startAfter the index of the item after which the search should commence.
     *                              `null` means that the search will start from the first item of the
     *                              bytemap (if `$howMany` is positive) or from the last item of the
     *                              bytemap (if `$howMany` is negative)
     *
     * @return \Generator items found (including their keys)
     */
    public function find(?iterable $items = null, bool $whitelist = true, int $howMany = \PHP_INT_MAX, ?int $startAfter = null): \Generator;

    /**
     * Returns the items that either match or do not match any of certain POSIX regular expressions.
     *
     * @param iterable $patterns   POSIX regular expressions that the items will be tested against
     * @param bool     $whitelist  `true` if the first argument represents a whitelist
     * @param int      $howMany    The maximum number of matches. By default, all the matches are included.
     *                             If negative, the search starts from the end.
     * @param ?int     $startAfter the index of the item after which the search should commence.
     *                             `null` means that the search will start from the first item of the
     *                             bytemap (if `$howMany` is positive) or from the last item of the
     *                             bytemap (if `$howMany` is negative)
     *
     * @throws \UnexpectedValueException if any pattern fails to compile
     *
     * @return \Generator items found (including their keys)
     */
    public function grep(iterable $patterns, bool $whitelist = true, int $howMany = \PHP_INT_MAX, ?int $startAfter = null): \Generator;

    /**
     * Inserts items at a certain index.
     *
     * Subsequent bytemap items are shifted right by the number of items inserted.
     *
     * @param iterable $items          The items to be inserted. Keys are ignored.
     * @param int      $firstItemIndex the index that the first newly inserted item is going to have.
     *                                 If negative, `-1` represents the last item, `-2` the item preceding it, etc
     *
     * @throws \TypeError       if any item that is to be inserted is not of the expected type
     * @throws \DomainException if any item found in the JSON stream is of the expected type,
     *                          but does not belong to the data domain of the bytemap
     */
    public function insert(iterable $items, int $firstItemIndex = -1): void;

    /**
     * Delete a certain number of items.
     *
     * Subsequent bytemap items are shifted left by the number of items deleted.
     *
     * @param int $firstItemIndex the index of the first item that is to be deleted.
     *                            If negative, `-1` represents the last item, `-2` the item preceding it, etc
     * @param int $howMany        the number of items to be deleted from the bytemap
     */
    public function delete(int $firstItemIndex = -1, int $howMany = \PHP_INT_MAX): void;

    /**
     * Writes a JSON representation of the bytemap to a stream.
     *
     * The stream is not closed afterwards.
     *
     * @param resource $stream the stream to write a JSON representation of the bytemap to
     *
     * @throws \TypeError                if the argument is not an open resource
     * @throws \InvalidArgumentException if the argument is an open resource that is not a stream
     * @throws \RuntimeException         if an error occurs when the stream is written to
     */
    public function streamJson($stream): void;

    /**
     * Creates a bytemap corresponding to a serialized JSON object or a serialized JSON array.
     *
     * The keys must be natural numbers.
     * The decoded values must be strings of the same length.
     *
     * @param resource $jsonStream  a stream with JSON data
     * @param string   $defaultItem the default item of the resulting bytemap
     *
     * @throws \TypeError                if the argument is not an open resource
     * @throws \InvalidArgumentException if the argument is an open resource that is not a stream
     * @throws \RuntimeException         if the stream cannot be read
     * @throws \UnexpectedValueException if the stream cannot be parsed as JSON
     * @throws \UnexpectedValueException if the parsed JSON stream has unexpected structure
     * @throws \TypeError                if any index found in the JSON stream is not an integer
     * @throws \OutOfRangeException      if any integer index found in the JSON stream is negative
     * @throws \TypeError                if any item found in the JSON stream is not of the expected type
     * @throws \DomainException          if any item found in the JSON stream is of the expected type,
     *                                   but does not belong to the data domain of the bytemap
     *
     * @return self a bytemap corresponding to the JSON data
     */
    public static function parseJsonStream($jsonStream, $defaultItem): self;
}

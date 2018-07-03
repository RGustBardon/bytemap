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
 * A vector of strings of the same length.
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
     * @param ?iterable $items     The items to look for (whitelist) or to ignore (blacklist).
     *                             `null` means all the items except for the default one.
     * @param bool      $whitelist `true` if the first argument is a whitelist
     * @param int       $howMany   The maximum number of matches. By default, all the matches are included.
     *                             If negative, the search starts from the end.
     *
     * @return \Generator items found (including their keys)
     */
    public function find(?iterable $items = null, bool $whitelist = true, int $howMany = \PHP_INT_MAX): \Generator;

    /**
     * Returns the items that either match or do not match a certain POSIX regular expression.
     *
     * @param string $regex     the POSIX regular expression that the items will be tested against
     * @param int    $howMany   The maximum number of matches. By default, all the matches are included.
     *                          If negative, the search starts from the end.
     * @param bool   $whitelist `true` if the first argument represents a whitelist
     *
     * @return \Generator items found (including their keys)
     */
    public function grep(string $regex, bool $whitelist = true, int $howMany = \PHP_INT_MAX): \Generator;

    /**
     * Inserts items at a certain offset.
     *
     * Subsequent bytemap items are shifted right by the number of items inserted.
     *
     * @param iterable $items           The items to be inserted. Keys are ignored.
     * @param int      $firstItemOffset The offset that the first newly inserted item is going to have.
     *                                  If negative, `-1` represents the last item, `-2` the item preceding it, etc.
     */
    public function insert(iterable $items, int $firstItemOffset = -1): void;

    /**
     * Delete a certain number of items.
     *
     * Subsequent bytemap items are shifted left by the number of items deleted.
     *
     * @param int $firstItemOffset The offset of the first item that is to be deleted.
     *                             If negative, `-1` represents the last item, `-2` the item preceding it, etc.
     * @param int $howMany         the number of items to be deleted from the bytemap
     */
    public function delete(int $firstItemOffset = -1, int $howMany = \PHP_INT_MAX): void;

    /**
     * Writes a JSON representation of the bytemap to a stream.
     *
     * The stream is not closed afterwards.
     *
     * @param resource $stream the stream to write a JSON representation of the bytemap to
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
     * @return self a bytemap corresponding to the JSON data
     */
    public static function parseJsonStream($jsonStream, $defaultItem): self;
}

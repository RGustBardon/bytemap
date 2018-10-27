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

interface ArrayProxyInterface extends ProxyInterface
{
    // Bytemap encapsulation

    /**
     * Instantiates a proxy to a certain bytemap.
     *
     * @param BytemapInterface $bytemap a bytemap that the proxy is to act for
     *
     * @return self a proxy to the bytemap
     */
    public static function wrap(BytemapInterface $bytemap): self;

    // Array conversion

    /**
     * Returns an array corresponding to the underyling bytemap.
     *
     * @return string[] an array whose keys are the indicies of a bytemap and whose values are
     *                  their corresponding items
     */
    public function exportArray(): array;

    /**
     * Instantiates a bytemap and a proxy to it.
     *
     * @param string   $defaultItem the default item of the underyling bytemap that is to be
     *                              constructed
     * @param iterable $items       the items that are to be inserted into the bytemap
     *
     * @return self a proxy to a bytemap that has been constructed based on the arguments
     */
    public static function import(string $defaultItem, iterable $items): self;

    // Array API

    /**
     * `\array_chunk` (split the bytemap into chunks).
     *
     * @param int  $size         the number of items in each chunk
     * @param bool $preserveKeys `true` if the indices in the array of generated values should
     *                           correspond to the offsets of bytemap items, `false` otherwise
     *
     * @return \Generator a generator whose values are arrays representing consecutive chunks of
     *                    the bytemap
     */
    public function chunk(int $size, bool $preserveKeys = false): \Generator;

    /**
     * `\array_count_values` (counts the frequency of each item of the bytemap).
     *
     * @return int[] an array whose keys are the items of the bytemap and whose values are the
     *               number of times each item appears in the bytemap
     */
    public function countValues(): array;

    /**
     * `\array_diff` (generates the items which are missing from all of the iterables).
     *
     * @param iterable ...$iterables iterables of items that must not appear in the result
     *
     * @return \Generator a generator whose values are the items that are found in the bytemap
     *                    but not in any of the iterables and whose keys are the offsets of those
     *                    items
     */
    public function diff(iterable ...$iterables): \Generator;

    /**
     * `\array_filter` (filters the items of the bytemap using a callback).
     *
     * @param null|callable $callback `null` if only the items other than `'0'` are to preserved,
     *                                a callback to determine which items are to be preserved
     *                                otherwise (if the callback returns `true`, the item is going
     *                                to be preserved)
     * @param int           $flag     `0` if only the item is to be passed to the callback,
     *                                `\ARRAY_FILTER_USE_KEY` if only the offset is to be passed to
     *                                the callback, `\ARRAY_FILTER_USE_BOTH` if both the item and
     *                                the offset are to be passed to the callback (in that order)
     *
     * @return \Generator a generator whose values are the items that pass the filter and whose
     *                    keys are their corresponding offsets
     */
    public function filter(?callable $callback = null, int $flag = 0): \Generator;

    /**
     * `\in_array` (checks if an item exists in the bytemap).
     *
     * @param string $needle the value to look for
     *
     * @return bool `true` if the needle is found, `false` otherwise
     */
    public function inArray(string $needle): bool;

    /**
     * `\array_key_exists` (checks if an offset exists in the bytemap).
     *
     * @param int $key the offset to look for
     *
     * @return bool `true` if the offset is found, `false` otherwise
     */
    public function keyExists(int $key): bool;

    /**
     * `\array_key_first` (returns the first offset of the bytemap).
     *
     * @return null|int `null` if the bytemap is empty, `0` otherwise
     */
    public function keyFirst(): ?int;

    /**
     * `\array_key_last` (returns the last offset of the bytemap).
     *
     * @return null|int `null` if the bytemap is empty, the last offset otherwise
     */
    public function keyLast(): ?int;

    /**
     * `\array_keys` (returns either all the offsets of the bytemap or their subset).
     *
     * @param null|string $searchValue the item to look for.
     *                                 `null` if all the offsets are to be returned
     *
     * @return \Generator if a search value has been specified, only the offsets whose
     *                    items are stricly equal to that value are generated (sorted ascending),
     *                    otherwise all the offsets are generated (sorted ascending)
     */
    public function keys(?string $searchValue = null): \Generator;

    /**
     * `\array_map` (generates the result of applying a callback to each item of the bytemap).
     *
     * @param null|callable $callback     `null` if the items are to be generated without any
     *                                    modification, a callback to be applied to the item
     *                                    and the corresponding arguments otherwise
     * @param iterable      ...$arguments optional arguments that are iterated synchronously
     *                                    with the bytemap and whose corresponding values are
     *                                    passed to the callback
     *
     * @return \Generator a generator whose values are either the items of the bytemap (if no
     *                    optional arguments are passed and the callback is `null`), or arrays
     *                    with the values obtained during each iteration (if optional arguments
     *                    are passed and the callback is `null`), or the results of applying
     *                    the callback to the values obtained during each iteration
     */
    public function map(?callable $callback, iterable ...$arguments): \Generator;

    /**
     * `\array_merge` (generates the items of the bytemap followed by the values of iterables).
     *
     * @param iterable ...$iterables iterables whose values will be generated after the items
     *                               of the bytemap
     *
     * @return \Generator consecutive items of the bytemap followed by the values found in
     *                    the iterables
     */
    public function merge(iterable ...$iterables): \Generator;

    /**
     * `\natcasesort` (sorts the bytemap in natural order, ignoring the case).
     *
     * @see https://github.com/sourcefrog/natsort The description of the algorithm.
     */
    public function natCaseSort(): void;

    /**
     * `\natsort` (sorts the bytemap in natural order, case-sensitively).
     *
     * @see https://github.com/sourcefrog/natsort The description of the algorithm.
     */
    public function natSort(): void;

    /**
     * `\array_pad`.
     *
     * @param int    $size
     * @param string $value
     *
     * @return \Generator
     */
    public function pad(int $size, string $value): \Generator;

    /**
     * `\array_pop` (pops an item off the end of the bytemap).
     *
     * @return null|string `null` if the bytemap has already been empty,
     *                     the removed item otherwise
     */
    public function pop(): ?string;

    /**
     * `\array_push` (pushes one or more items onto the end of the bytemap).
     *
     * @param string ...$values The items to be pushed.
     *
     * @return int the new number of the items in the bytemap
     */
    public function push(string ...$values): int;

    /**
     * `\array_rand`.
     *
     * @param int $num
     *
     * @return null|array|string
     */
    public function rand(int $num = 1);

    /**
     * `\array_reduce`.
     *
     * @param callable $callback
     * @param mixed    $initial
     *
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null);

    /**
     * `\array_replace`.
     *
     * @param iterable ...$iterables
     *
     * @return self
     */
    public function replace(iterable ...$iterables): self;

    /**
     * `\array_reverse` (generates the items in reverse order).
     *
     * @param bool $preserveKeys `true` if the offsets are to be preserved,
     *                           `false` otherwise
     *
     * @return \Generator a generator whose values are the items of the bytemap in the reverse
     *                    order and whose keys are either the corresponding offsets or
     *                    a sequence of consecutive natural numbers starting from 0
     */
    public function reverse(bool $preserveKeys = false): \Generator;

    /**
     * `\rsort`.
     *
     * @param int $sortFlags
     */
    public function rSort(int $sortFlags = \SORT_REGULAR): void;

    /**
     * `\array_search`.
     *
     * @param string $needle
     *
     * @return false|int
     */
    public function search(string $needle);

    /**
     * `\array_shift` (shifts an element off the beginning of the bytemap).
     *
     * @return null|string `null` if the bytemap has already been empty,
     *                     the removed item otherwise
     */
    public function shift(): ?string;

    /**
     * `\shuffle` (randomizes the orders of bytemap items).
     *
     * Uses a pseudorandom number generator (`\mt_rand`).
     */
    public function shuffle(): void;

    /**
     * `\sizeof` (an alias of `\count`).
     *
     * @return int the number of items in the bytemap
     */
    public function sizeOf(): int;

    /**
     * `\array_slice`.
     *
     * @param int      $offset
     * @param null|int $length
     * @param bool     $preserveKeys
     *
     * @return \Generator
     */
    public function slice(int $offset, ?int $length = null, bool $preserveKeys = false): \Generator;

    /**
     * `\sort`.
     *
     * @param int $sortFlags
     */
    public function sort(int $sortFlags = \SORT_REGULAR): void;

    /**
     * `\array_splice`.
     *
     * @param int      $offset
     * @param null|int $length
     * @param mixed    $replacement
     *
     * @return self
     */
    public function splice(int $offset, ?int $length = null, $replacement = []): self;

    /**
     * The equivalent of the array `+` operator.
     *
     * @param iterable ...$iterables
     *
     * @return self
     */
    public function union(iterable ...$iterables): self;

    /**
     * `\array_unique`.
     *
     * @param int $sortFlags
     *
     * @return \Generator
     */
    public function unique(int $sortFlags = \SORT_STRING): \Generator;

    /**
     * `\array_unshift` (prepends items to the beginning of the bytemap).
     *
     * @param string ...$values the items to prepend
     *
     * @return int the new number of items in the bytemap
     */
    public function unshift(string ...$values): int;

    /**
     * `\usort` (sorts the bytemap by comparing items using a user-defined function).
     *
     * Sorting is not stable.
     *
     * @param callable $valueCompareFunc a function whose parameters are two strings (the items
     *                                   being compared) and whose return value will be cast to
     *                                   an integer and interpreted as follows: negative integers
     *                                   indicate that the first element should appear before the
     *                                   second one, positive integers indicate that the first
     *                                   element should appear after the second one, and 0
     *                                   indicates that neither of these cases applies
     */
    public function uSort(callable $valueCompareFunc): void;

    /**
     * `\array_values` (generates all the items of the bytemap).
     *
     * Using this generator is equivalent to iterating over the bytemap directly.
     *
     * @return \Generator A generator whose values are bytemap items and whose keys are the offsets
     *                    of those items. Keys are generated in the ascending order.
     */
    public function values(): \Generator;

    /**
     * `\array_walk`.
     *
     * @param callable $callback
     * @param mixed    $userdata
     */
    public function walk(callable $callback, $userdata = null): void;

    /**
     * `\array_combine` (creates a bytemap by using one iterable for keys and another for its values).
     *
     * @param string   $defaultItem the default item of the underlying bytemap
     * @param iterable $keys        keys to be used.
     *                              They need not be consecutive. All the missing keys between 0
     *                              and the maximum key will be assigned the default value.
     * @param iterable $values      values to be used
     *
     * @return self the combined bytemap
     */
    public static function combine(string $defaultItem, iterable $keys, iterable $values): self;

    /**
     * `\array_fill` (fills a bytemap with values).
     *
     * @param string      $defaultItem the default item of the underlying bytemap
     * @param int         $startIndex  the first index of the value used for filling
     * @param int         $num         number of elements to insert
     * @param null|string $value       value to use for filling.
     *                                 `null` means that the default item will be used
     */
    public static function fill(string $defaultItem, int $startIndex, int $num, ?string $value = null): self;

    /**
     * `\array_fill_keys` (fills a bytemap with values, specifying keys).
     *
     * @param string      $defaultItem the default item of the underlying bytemap
     * @param iterable    $keys        values that will be used as keys
     * @param null|string $value       value to use for filling
     *                                 `null` means that the default item will be used
     */
    public static function fillKeys(string $defaultItem, iterable $keys, ?string $value = null): self;

    // PCRE API

    /**
     * `\preg_filter`.
     *
     * @param iterable|string $pattern
     * @param iterable|string $replacement
     * @param int             $limit
     * @param null|int        $count
     *
     * @return \Generator
     */
    public function pregFilter($pattern, $replacement, int $limit = -1, ?int &$count = 0): \Generator;

    /**
     * `\preg_grep` (returns bytemap items that match the pattern).
     *
     * @param string $pattern the pattern to search for
     * @param int    $flags   if set to `PREG_GREP_INVERT`, returns the items that do NOT match
     *                        the given pattern
     *
     * @return \Generator the items indexed using the offsets from the bytemap
     */
    public function pregGrep(string $pattern, int $flags = 0): \Generator;

    /**
     * `\preg_replace`.
     *
     * @param iterable|string $pattern
     * @param iterable|string $replacement
     * @param int             $limit
     * @param null|int        $count
     *
     * @return \Generator
     */
    public function pregReplace($pattern, $replacement, int $limit = -1, ?int &$count = 0): \Generator;

    /**
     * `\preg_replace_callback`.
     *
     * @param iterable|string $pattern
     * @param callable        $callback
     * @param int             $limit
     * @param null|int        $count
     *
     * @return \Generator
     */
    public function pregReplaceCallback($pattern, callable $callback, int $limit = -1, ?int &$count = 0): \Generator;

    /**
     * `\preg_replace_callback_array`.
     *
     * @param iterable $patternsAndCallbacks
     * @param int      $limit
     * @param null|int $count
     *
     * @return \Generator
     */
    public function pregReplaceCallbackArray(iterable $patternsAndCallbacks, int $limit = -1, ?int &$count = 0): \Generator;

    // String API

    /**
     * `\implode` (joins bytemap items with a string).
     *
     * @param string $glue a string that is to be inserted between the items
     *
     * @return string a representation of all the bytemap items in the same order,
     *                with the glue string between each item
     */
    public function implode(string $glue): string;

    /**
     * `\join` (an alias of `\implode`, joins bytemap items with a string).
     *
     * @param string $glue a string that is to be inserted between the items
     *
     * @return string a representation of all the bytemap items in the same order,
     *                with the glue string between each item
     */
    public function join(string $glue): string;
}

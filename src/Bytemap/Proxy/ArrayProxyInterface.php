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
     * @param BytemapInterface $bytemap
     *
     * @return self
     */
    public static function wrap(BytemapInterface $bytemap): self;

    // Array conversion

    /**
     * @return string[]
     */
    public function exportArray(): array;

    /**
     * @param string   $defaultItem
     * @param iterable $items
     *
     * @return self
     */
    public static function import(string $defaultItem, iterable $items): self;

    // Array API

    /**
     * `\array_chunk`.
     *
     * @param int  $size
     * @param bool $preserveKeys
     *
     * @return \Generator
     */
    public function chunk(int $size, bool $preserveKeys = false): \Generator;

    /**
     * `\array_count_values`.
     *
     * @return int[]
     */
    public function countValues(): array;

    /**
     * `\array_diff`.
     *
     * @param iterable ...$iterables
     *
     * @return \Generator
     */
    public function diff(iterable ...$iterables): \Generator;

    /**
     * `\array_filter`.
     *
     * @param callable $callback
     * @param int      $flag
     *
     * @return \Generator
     */
    public function filter(?callable $callback = null, int $flag = 0): \Generator;

    /**
     * `\in_array`.
     *
     * @param string $needle
     *
     * @return bool
     */
    public function inArray(string $needle): bool;

    /**
     * `\array_key_exists`.
     *
     * @param int $key
     *
     * @return bool
     */
    public function keyExists(int $key): bool;

    /**
     * `\array_key_first`.
     *
     * @return null|int
     */
    public function keyFirst(): ?int;

    /**
     * `\array_key_last`.
     *
     * @return null|int
     */
    public function keyLast(): ?int;

    /**
     * `\array_keys`.
     *
     * @param string $searchValue
     *
     * @return \Generator
     */
    public function keys(?string $searchValue = null): \Generator;

    /**
     * `\array_map`.
     *
     * @param null|callable $callback
     * @param iterable      ...$arguments
     *
     * @return \Generator
     */
    public function map(?callable $callback, iterable ...$arguments): \Generator;

    /**
     * `\array_merge`.
     *
     * @param iterable ...$iterables
     *
     * @return \Generator
     */
    public function merge(iterable ...$iterables): \Generator;

    /**
     * `\natcasesort`.
     */
    public function natCaseSort(): void;

    /**
     * `\natsort`.
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
     * `\array_pop`.
     *
     * @return null|string
     */
    public function pop(): ?string;

    /**
     * `\array_push`.
     *
     * @param string ...$values
     *
     * @return int
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
     * `\array_reverse`.
     *
     * @param bool $preserveKeys
     *
     * @return \Generator
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
     * `\array_shift`.
     *
     * @return null|string
     */
    public function shift(): ?string;

    /**
     * `\shuffle`.
     */
    public function shuffle(): void;

    /**
     * `\sizeof` (an alias of `\count`).
     *
     * @return int
     */
    public function sizeOf(): int;

    /**
     * `\array_slice`.
     *
     * @param int  $offset
     * @param int  $length
     * @param bool $preserveKeys
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
     * @param int   $offset
     * @param int   $length
     * @param mixed $replacement
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
     * `\array_unshift`.
     *
     * @param string ...$values
     *
     * @return int
     */
    public function unshift(string ...$values): int;

    /**
     * `\usort`.
     *
     * @param callable $valueCompareFunc
     */
    public function uSort(callable $valueCompareFunc): void;

    /**
     * `\array_values`.
     *
     * @return \Generator
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
     * @param string   $defaultItem the default item of the underlying bytemap
     * @param iterable $keys        values that will be used as keys
     * @param string   $value       value to use for filling
     */
    public static function fillKeys(string $defaultItem, iterable $keys, ?string $value = null): self;

    // PCRE API

    /**
     * `\preg_filter`.
     *
     * @param iterable|string $pattern
     * @param iterable|string $replacement
     * @param int             $limit
     * @param int             $count
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
     * @param int             $count
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
     * @param int             $count
     *
     * @return \Generator
     */
    public function pregReplaceCallback($pattern, callable $callback, int $limit = -1, ?int &$count = 0): \Generator;

    /**
     * `\preg_replace_callback_array`.
     *
     * @param iterable $patternsAndCallbacks
     * @param int      $limit
     * @param int      $count
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

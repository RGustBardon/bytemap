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
 * A proxy implementing part of the API found in the PHP core for native arrays.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
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
     * Returns an array corresponding to the underlying bytemap.
     *
     * @return string[] an array whose keys are the indices of a bytemap and whose values are
     *                  their corresponding elements
     */
    public function exportArray(): array;

    /**
     * Instantiates a bytemap and a proxy to it.
     *
     * @param string   $defaultValue the default value of the underlying bytemap that is to be
     *                               constructed
     * @param iterable $elements     the elements that are to be inserted into the bytemap
     *
     * @throws \DomainException if the default value is an empty string
     * @throws \TypeError       if any element that is to be inserted is not of the expected type
     * @throws \DomainException if any element that is to be inserted is of the expected type,
     *                          but does not belong to the data domain of the bytemap
     *
     * @return self a proxy to a bytemap that has been constructed based on the arguments
     */
    public static function import(string $defaultValue, iterable $elements): self;

    // Array API

    /**
     * `\array_chunk` (split the bytemap into chunks).
     *
     * @param int  $size         the number of elements in each chunk
     * @param bool $preserveKeys `true` if the keys of generated arrays should correspond to the
     *                           indices of bytemap elements, `false` otherwise
     *
     * @throws \OutOfRangeException if the size parameter is not positive
     *
     * @return \Generator a generator whose values are arrays representing consecutive chunks of
     *                    the bytemap
     */
    public function chunk(int $size, bool $preserveKeys = false): \Generator;

    /**
     * `\array_count_values` (counts the frequency of each element of the bytemap).
     *
     * @return int[] an array whose keys are the elements of the bytemap and whose values are the
     *               number of times each element appears in the bytemap
     */
    public function countValues(): array;

    /**
     * `\array_diff` (generates the elements which are missing from all of the iterables).
     *
     * @param iterable ...$iterables iterables of elements that must not appear in the result
     *
     * @return \Generator a generator whose values are the elements that are found in the bytemap
     *                    but not in any of the iterables and whose keys are the indices of those
     *                    elements
     */
    public function diff(iterable ...$iterables): \Generator;

    /**
     * `\array_filter` (filters the elements of the bytemap using a callback).
     *
     * @param null|callable $callback `null` if only the elements other than `'0'` are to preserved,
     *                                a callback to determine which elements are to be preserved
     *                                otherwise (if the callback returns `true`, the element is going
     *                                to be preserved)
     * @param int           $flag     `0` if only an element is to be passed to the callback,
     *                                `\ARRAY_FILTER_USE_KEY` if only an index is to be passed to
     *                                the callback, `\ARRAY_FILTER_USE_BOTH` if both an element and
     *                                its index are to be passed to the callback (in that order)
     *
     * @return \Generator a generator whose values are the elements that pass the filter and whose
     *                    keys are their corresponding indices
     */
    public function filter(?callable $callback = null, int $flag = 0): \Generator;

    /**
     * `array_flip` (generates indices as values and their corresponding elements as keys).
     *
     * @return \Generator a generator whose values are the indices of the bytemap and whose keys
     *                    are its corresponding elements
     */
    public function flip(): \Generator;

    /**
     * `\in_array` (checks if an element exists in the bytemap).
     *
     * @param string $needle the value to look for
     *
     * @return bool `true` if the needle is found, `false` otherwise
     */
    public function inArray(string $needle): bool;

    /**
     * `array_intersect` (generates values that appear in the bytemap and in every argument).
     *
     * Values are compared as strings.
     *
     * If a value is to be generated and it appears more than once in the bytemap, it will be
     * generated that many times with the indices of the elements who are equal to that value.
     *
     * @param iterable ...$iterables iterables whose values are to be intersected with the elements
     *
     * @return \Generator a generator whose values are the values of the bytemap which appear in all
     *                    the iterables and whose keys are the corresponding indices these values
     *                    are associated with in the bytemap
     */
    public function intersect(iterable ...$iterables): \Generator;

    /**
     * `\array_key_exists` (checks if an index exists in the bytemap).
     *
     * @param int $key the index to look for
     *
     * @return bool `true` if the index is found, `false` otherwise
     */
    public function keyExists(int $key): bool;

    /**
     * `\array_key_first` (returns the first index of the bytemap).
     *
     * @throws \UnderflowException if the bytemap contains no elements
     *
     * @return int `0`
     */
    public function keyFirst(): int;

    /**
     * `\array_key_last` (returns the last index of the bytemap).
     *
     * @throws \UnderflowException if the bytemap contains no elements
     *
     * @return int the last index
     */
    public function keyLast(): int;

    /**
     * `\array_keys` (returns either all the indices of the bytemap or their subset).
     *
     * @param null|string $searchValue the element to look for.
     *                                 `null` if all the indices are to be returned
     *
     * @return \Generator if a search value has been specified, only the indices whose
     *                    elements are strictly equal to that value are generated (sorted
     *                    ascending), otherwise all the indices are generated (sorted
     *                    ascending)
     */
    public function keys(?string $searchValue = null): \Generator;

    /**
     * `\array_map` (generates the result of applying a callback to each element of the bytemap).
     *
     * @param null|callable $callback     `null` if the elements are to be generated without any
     *                                    modification, a callback to be applied to the element
     *                                    and the corresponding arguments otherwise
     * @param iterable      ...$arguments optional arguments that are iterated synchronously
     *                                    with the bytemap and whose corresponding values are
     *                                    passed to the callback
     *
     * @throws \InvalidArgumentException if any of the provided iterables is neither an array,
     *                                   nor an `\Iterator`, nor an `\IteratorAggregate`
     *
     * @return \Generator a generator whose values are either the elements of the bytemap (if no
     *                    optional arguments are passed and the callback is `null`), or arrays
     *                    with the values obtained during each iteration (if optional arguments
     *                    are passed and the callback is `null`), or the results of applying
     *                    the callback to the values obtained during each iteration
     */
    public function map(?callable $callback, iterable ...$arguments): \Generator;

    /**
     * `\array_merge` (generates the elements of the bytemap followed by the values of iterables).
     *
     * @param iterable ...$iterables iterables whose values will be generated after the elements
     *                               of the bytemap
     *
     * @return \Generator consecutive elements of the bytemap followed by the values found in
     *                    the iterables
     */
    public function merge(iterable ...$iterables): \Generator;

    /**
     * `array_multisort` (sorts the bytemap and reorders supported iterables the same way).
     *
     * The sorting being performed is not stable.
     *
     * Any key of any array passed to this method is going to be re-indexed if it is a natural
     * number.
     *
     * @param int                                                $sortFlags             if `\SORT_NUMERIC`, elements are converted to
     *                                                                                  floating point numbers before being compared,
     *                                                                                  otherwise,
     *                                                                                  if `\SORT_LOCALE_STRING`, the comparison is
     *                                                                                  locale-based and case-sensitive (`\strcoll`),
     *                                                                                  otherwise,
     *                                                                                  if `\SORT_NATURAL`, the bytemap is sorted in
     *                                                                                  natural order (in a case-sensitive fashion if not
     *                                                                                  combined with `\SORT_FLAG_CASE`), otherwise,
     *                                                                                  if `\SORT_REGULAR`, numeric elements are converted
     *                                                                                  to floating point numbers are then to strings before
     *                                                                                  being compared, whereas other elements are compared
     *                                                                                  unchanged (in a case-sensitive fashion), otherwise,
     *                                                                                  elements are compared in a binary safe fashion (and
     *                                                                                  also in a case-sensitive fashion if not combined
     *                                                                                  with `\SORT_FLAG_CASE`);
     * @param bool                                               $ascending             `true` if sorting ascending, `false` otherwise
     * @param array|BytemapInterface|\Ds\Sequence|\SplFixedArray ...$iterablesToReorder iterables that are to be reordered the way the
     *                                                                                  elements of the bytemap are going to be during
     *                                                                                  sorting
     *
     * @throws \TypeError          if any iterable passed to the method is of an unsupported type
     * @throws \UnderflowException if the bytemap and all the iterables do not have the same number
     *                             of elements
     */
    public function multiSort(int $sortFlags = \SORT_REGULAR, bool $ascending = true, &...$iterablesToReorder): void;

    /**
     * `\natcasesort` (sorts the bytemap in natural order, ignoring the case).
     *
     * @see https://github.com/sourcefrog/natsort The description of the algorithm.
     *
     * @throws \RuntimeException if the `\strnatcasecmp` function is not available
     */
    public function natCaseSort(): void;

    /**
     * `\natsort` (sorts the bytemap in natural order, case-sensitively).
     *
     * @see https://github.com/sourcefrog/natsort The description of the algorithm.
     *
     * @throws \RuntimeException if the `\strnatcasecmp` function is not available
     */
    public function natSort(): void;

    /**
     * `\array_pad` (generates a certain value repeatedly and all the elements of the bytemap).
     *
     * @param int    $size  the number of values to generate (if positive, additional values will
     *                      be generated after the elements of the bytemap, otherwise they will come
     *                      first; if the absolute value is less than the current number of elements
     *                      in the bytemap, all of the elements of the bytemap will be generated
     *                      nonetheless)
     * @param string $value the value to repeatedly generate in addition to all the elements of the
     *                      bytemap
     *
     * @return \Generator the elements of the bytemap, all of which, as a sequence, are either
     *                    preceded or followed by a repeated string
     */
    public function pad(int $size, string $value): \Generator;

    /**
     * `\array_pop` (pops an element off the end of the bytemap).
     *
     * @throws \UnderflowException if the bytemap contains no elements
     *
     * @return string the removed element
     */
    public function pop(): string;

    /**
     * `\array_push` (pushes one or more elements onto the end of the bytemap).
     *
     * @param string ...$values The elements to be pushed.
     *
     * @throws \TypeError       if any element that is to be inserted is not of the expected type
     * @throws \DomainException if any element that is to be inserted is of the expected type,
     *                          but does not belong to the data domain of the bytemap
     *
     * @return int the new number of the elements in the bytemap
     */
    public function push(string ...$values): int;

    /**
     * `\array_rand` (picks one or more random indices of the bytemap).
     *
     * @param int $num the number of indices to pick
     *
     * @throws \UnderflowException  if the bytemap contains no elements
     * @throws \OutOfRangeException if `$num` is less than 1 or greater than the number of elements
     *                              in the bytemap
     *
     * @return int|int[] a single index if `$num` is equal to 1, an array of random indices
     *                   otherwise
     */
    public function rand(int $num = 1);

    /**
     * `\array_reduce` (reduces the bytemap to a single value with an iteratively applied callback).
     *
     * @param callable $callback a callable whose first argument is the return value from the
     *                           previous iteration and whose second argument is the current element
     *                           of the bytemap
     * @param mixed    $initial  the value to pass to the callback when it receives the element with
     *                           index `0`
     *
     * @return mixed the value returned by the last call to the callback
     */
    public function reduce(callable $callback, $initial = null);

    /**
     * `\array_replace` (returns the bytemap with its elements overwritten by iterables).
     *
     * An iterable passed after another iterable overwrites its value when their keys match.
     *
     * @param iterable ...$iterables iterables whose values are to overwrite the values in a
     *                               clone of the bytemap
     *
     * @throws \TypeError       if any element that is to overwrite an existing one is not of the
     *                          expected type
     * @throws \DomainException if any element that is to overwrite an existing one is of the
     *                          expected type, but does not belong to the data domain of the bytemap
     *
     * @return self a clone of the bytemap who has had its elements overwritten by the values
     *              found in the iterables in cases where indices matched the keys
     */
    public function replace(iterable ...$iterables): self;

    /**
     * `\array_reverse` (generates the elements in reverse order).
     *
     * @param bool $preserveKeys `true` if the indices are to be preserved, `false` otherwise
     *
     * @return \Generator a generator whose values are the elements of the bytemap in the reverse
     *                    order and whose keys are either the corresponding indices or
     *                    a sequence of consecutive natural numbers starting from 0
     */
    public function reverse(bool $preserveKeys = false): \Generator;

    /**
     * `\rSort` (sorts the bytemap in the descending order).
     *
     * @param int $sortFlags if `\SORT_NUMERIC`, elements are converted to floating point numbers
     *                       before being compared, otherwise,
     *                       if `\SORT_LOCALE_STRING`, the comparison is locale-based and
     *                       case-sensitive (`\strcoll`), otherwise,
     *                       if `\SORT_NATURAL`, the bytemap is sorted in natural order
     *                       (in a case-sensitive fashion if not combined with `\SORT_FLAG_CASE`),
     *                       otherwise,
     *                       if `\SORT_REGULAR`, numeric elements are converted to floating point
     *                       numbers are then to strings before being compared, whereas other
     *                       elements are compared unchanged (in a case-sensitive fashion),
     *                       otherwise,
     *                       elements are compared in a binary safe fashion (and also in a
     *                       case-sensitive fashion if not combined with `\SORT_FLAG_CASE`)
     */
    public function rSort(int $sortFlags = \SORT_REGULAR): void;

    /**
     * `\array_search` (returns the first index of a given value).
     *
     * @param string $needle the value to look for in the bytemap
     *
     * @return false|int `false` if the bytemap does not contain the value,
     *                   the index of the first match otherwise
     */
    public function search(string $needle);

    /**
     * `\array_shift` (shifts an element off the beginning of the bytemap).
     *
     * @throws \UnderflowException if the bytemap contains no elements
     *
     * @return string the removed element
     */
    public function shift(): string;

    /**
     * `\shuffle` (randomizes the orders of bytemap elements).
     *
     * Uses a pseudorandom number generator (`\mt_rand`).
     */
    public function shuffle(): void;

    /**
     * `\sizeof` (an alias of `\count`).
     *
     * @return int the number of elements in the bytemap
     */
    public function sizeOf(): int;

    /**
     * `\array_slice` (generates a slice of the bytemap).
     *
     * @param int      $index        the index at which the slicing should commence (if negative,
     *                               it will start that far from the end of the bytemap)
     * @param null|int $length       if `null`, slicing will stop after the last element, otherwise,
     *                               if negative, slicing will stop that far from the end of the
     *                               bytemap,
     *                               otherwise, it is the maximum number of elements in the slice
     * @param bool     $preserveKeys `true` if the elements are to be generated with their indices,
     *                               `false` if keys should start from `0`
     *
     * @return \Generator a generator whose values are the elements included in the slice and whose
     *                    keys are either their indices or a sequence of natural numbers starting
     *                    from `0`
     */
    public function slice(int $index, ?int $length = null, bool $preserveKeys = false): \Generator;

    /**
     * `\sort` (sorts the bytemap in the ascending order).
     *
     * @param int $sortFlags if `\SORT_NUMERIC`, elements are converted to floating point numbers
     *                       before being compared, otherwise,
     *                       if `\SORT_LOCALE_STRING`, the comparison is locale-based and
     *                       in a case-sensitive fashion (`\strcoll`), otherwise,
     *                       if `\SORT_NATURAL`, the bytemap is sorted in natural order
     *                       (in a case-sensitive fashion if not combined with `\SORT_FLAG_CASE`),
     *                       otherwise,
     *                       if `\SORT_REGULAR`, numeric elements are converted to floating point
     *                       numbers are then to strings before being compared, whereas other
     *                       elements are compared unchanged (in a case-sensitive fashion),
     *                       otherwise,
     *                       elements are compared in a binary safe fashion (and also in a
     *                       case-sensitive fashion if not combined with `\SORT_FLAG_CASE`)
     */
    public function sort(int $sortFlags = \SORT_REGULAR): void;

    /**
     * `\array_splice` (replaces a slice of the bytemap).
     *
     * @param int      $index       the index at which the slicing should commence (if negative,
     *                              it will start that far from the end of the bytemap)
     * @param null|int $length      if `null`, slicing will stop after the last element, otherwise,
     *                              if negative, slicing will stop that far from the end of the
     *                              bytemap,
     *                              otherwise, it is the maximum number of elements in the slice
     * @param mixed    $replacement values to replace the slice with (if something other than an
     *                              iterable is passed, it will be converted to an array)
     *
     * @throws \TypeError       if any element that is to be inserted is not of the expected type
     * @throws \DomainException if any element that is to be inserted is of the expected type,
     *                          but does not belong to the data domain of the bytemap
     *
     * @return self the extracted elements (indices are not preserved)
     */
    public function splice(int $index, ?int $length = null, $replacement = []): self;

    /**
     * The equivalent of the array `+` operator (appends elements to a clone of the bytemap).
     *
     * Every iterable whose greatest key is greater than the greatest index of the bytemap expands
     * the bytemap. Subsequent iterables must then contain values with keys greater than the
     * new greatest index in order to be appended to the result.
     *
     * @param iterable ...$iterables iterables whose values are to be appended to those of the
     *                               clone when their keys are greater than the greatest index
     *                               of the clone in the current iteration
     *
     * @throws \TypeError           if any of the provided iterables contains a value whose key is
     *                              is not an integer
     * @throws \OutOfRangeException if any of the provided iterables contains a value whose key is
     *                              negative
     * @throws \TypeError           if any of the provided iterables contains a value that is not
     *                              of the expected type
     * @throws \DomainException     if any of the provided iterables contains a value that is of
     *                              the expected type, but does not belong to the data domain of
     *                              the bytemap
     *
     * @return self a clone of the bytemap with potentially new elements but none overwritten
     */
    public function union(iterable ...$iterables): self;

    /**
     * `\array_unique` (generates unique values of elements in the order they first appear).
     *
     * @param int $sortFlags if `\SORT_NUMERIC`, elements are converted to floating point numbers
     *                       before being compared, otherwise,
     *                       if `\SORT_LOCALE_STRING`, the comparison is locale-based and
     *                       case-sensitive (`\strcoll`), otherwise,
     *                       if `\SORT_REGULAR`, numeric elements are converted to floating point
     *                       numbers are then to strings before being compared, whereas other
     *                       elements are compared unchanged (in a case-sensitive fashion),
     *                       otherwise,
     *                       elements are compared as strings (in a case-sensitive fashion)
     *
     * @return \Generator A generator whose values are unique bytemap elements and whose keys are
     *                    the indices of their first appearance in the bytemap. Keys are generated
     *                    in the ascending order.
     */
    public function unique(int $sortFlags = \SORT_STRING): \Generator;

    /**
     * `\array_unshift` (prepends elements to the beginning of the bytemap).
     *
     * @param string ...$values the elements to prepend
     *
     * @throws \TypeError       if any element that is to be inserted is not of the expected type
     * @throws \DomainException if any element that is to be inserted is of the expected type,
     *                          but does not belong to the data domain of the bytemap
     *
     * @return int the new number of elements in the bytemap
     */
    public function unshift(string ...$values): int;

    /**
     * `\usort` (sorts the bytemap by comparing elements using a user-defined function).
     *
     * Sorting is not stable.
     *
     * @param callable $valueCompareFunc a function whose parameters are two strings (the elements
     *                                   being compared) and whose return value will be converted
     *                                   to an integer and interpreted as follows: negative
     *                                   integers indicate that the first element should appear
     *                                   before the second one, positive integers indicate that
     *                                   the first element should appear after the second one, and
     *                                   `0` indicates that neither of these cases applies
     */
    public function uSort(callable $valueCompareFunc): void;

    /**
     * `\array_values` (generates all the elements of the bytemap).
     *
     * Using this generator is equivalent to iterating over the bytemap directly.
     *
     * @return \Generator A generator whose values are bytemap elements and whose keys are the indices
     *                    of those elements. Keys are generated in the ascending order.
     */
    public function values(): \Generator;

    /**
     * `\array_walk` (applies a callback to every element of the bytemap).
     *
     * If the callback receives the element as reference, any change to the element will be
     * reflected in the bytemap.
     *
     * @param callable $callback a callback that will be passed an element (as the first argument),
     *                           its index (as the second argument), and, optionally, `$userdata`
     *                           as the third argument (if the third parameter had been declared)
     * @param mixed    $userdata an additional value to be passed to the callback if it expects
     *                           three arguments
     *
     * @throws \ArgumentCountError if the callback expects more arguments than it actually gets
     * @throws \TypeError          if the callback returns a value that is not of the expected type
     * @throws \DomainException    if the callback returns a value that is of the expected type,
     *                             but does not belong to the data domain of the bytemap
     */
    public function walk(callable $callback, $userdata = null): void;

    /**
     * `\array_combine` (creates a bytemap by using one iterable for indices and another for its elements).
     *
     * @param string   $defaultValue the default value of the underlying bytemap
     * @param iterable $keys         indices to be used.
     *                               They need not be consecutive. All the missing elements between 0
     *                               and the maximum index will be assigned the default value.
     * @param iterable $values       values to be used
     *
     * @throws \DomainException     if the default value is an empty string
     * @throws \UnderflowException  if `$keys` and `$values` do not contain the same number of
     *                              elements
     * @throws \TypeError           if `$keys` contain a value that is is not an integer
     * @throws \OutOfRangeException if `$keys` contain a value that is negative
     * @throws \TypeError           if `$values` contain a value that is not of the expected type
     * @throws \DomainException     if `$values` contain a value that is of the expected type, but
     *                              does not belong to the data domain of the bytemap
     *
     * @return self the combined bytemap
     */
    public static function combine(string $defaultValue, iterable $keys, iterable $values): self;

    /**
     * `\array_fill` (fills a bytemap with values).
     *
     * @param string      $defaultValue the default value of the underlying bytemap
     * @param int         $startIndex   the first index of the value used for filling
     * @param int         $num          the number of elements to insert
     * @param null|string $value        the value to use for filling.
     *                                  `null` means that the default value will be used
     *
     * @throws \DomainException     if the default value is an empty string
     * @throws \OutOfRangeException if `$startIndex` is negative
     * @throws \OutOfRangeException if `$num` is negative
     * @throws \TypeError           if the value that is to be inserted is not of the expected type
     * @throws \DomainException     if the value that is to be inserted is of the expected type,
     *                              but does not belong to the data domain of the bytemap
     */
    public static function fill(string $defaultValue, int $startIndex, int $num, ?string $value = null): self;

    /**
     * `\array_fill_keys` (fills a bytemap with a value, specifying indices).
     *
     * @param string      $defaultValue the default value of the underlying bytemap
     * @param iterable    $keys         values that will be used as indices
     * @param null|string $value        value to use for filling
     *                                  `null` means that the default value will be used
     *
     * @throws \DomainException     if the default value is an empty string
     * @throws \TypeError           if `$keys` contain a value that is is not an integer
     * @throws \OutOfRangeException if `$keys` contain a value that is negative
     * @throws \TypeError           if the value that is to be inserted is not of the expected type
     * @throws \DomainException     if the value that is to be inserted is of the expected type,
     *                              but does not belong to the data domain of the bytemap
     */
    public static function fillKeys(string $defaultValue, iterable $keys, ?string $value = null): self;

    // PCRE API

    /**
     * `\preg_filter` (generates pattern matching elements, transforming generated values).
     *
     * @param iterable|string $pattern     PCRE pattern(s) to search for
     * @param iterable|string $replacement a string defining a replacement or an iterable of such
     *                                     strings (can contain backreferences such as `${1}`)
     * @param int             $limit       the maximum number of replacements of each match in a
     *                                     single element (`-1` corresponds to no limit)
     * @param null|int        $count       the total number of all the replacements performed in
     *                                     in all the elements that matched
     *
     * @throws \UnexpectedValueException if any pattern fails to compile
     *
     * @return \Generator a generator whose values are the matching elements of the bytemap after
     *                    replacements, after replacements otherwise) and whose keys are the indices
     *                    of those elements
     */
    public function pregFilter($pattern, $replacement, int $limit = -1, ?int &$count = 0): \Generator;

    /**
     * `\preg_grep` (generates bytemap elements that match a pattern).
     *
     * @param string $pattern a PCRE pattern to search for
     * @param int    $flags   if set to `PREG_GREP_INVERT`, returns the elements that do NOT match
     *                        the given pattern
     *
     * @throws \UnexpectedValueException if any pattern fails to compile
     *
     * @return \Generator the elements indexed using the indices from the bytemap
     */
    public function pregGrep(string $pattern, int $flags = 0): \Generator;

    /**
     * `\preg_replace` (generates all the elements, transforming matching values).
     *
     * @param iterable|string $pattern     PCRE pattern(s) to search for
     * @param iterable|string $replacement a string defining a replacement or an iterable of such
     *                                     strings (can contain backreferences such as `${1}`)
     * @param int             $limit       the maximum number of replacements of each match in a
     *                                     single element (`-1` corresponds to no limit)
     * @param null|int        $count       the total number of all the replacements performed in
     *                                     in all the elements that matched
     *
     * @throws \UnexpectedValueException if any pattern fails to compile
     *
     * @return \Generator a generator whose values are the elements of the bytemap (unchanged if not
     *                    matching, after replacements otherwise) and whose keys are the indices of
     *                    those elements
     */
    public function pregReplace($pattern, $replacement, int $limit = -1, ?int &$count = 0): \Generator;

    /**
     * `\preg_replace_callback` (generates all the elements, transforming matching values).
     *
     * @param iterable|string $pattern  PCRE pattern(s) to search for
     * @param callable        $callback a callback called whenever an element matches any of the
     *                                  patterns, which is passed an array of matches pertaining to
     *                                  that particular pattern and element and is expected to
     *                                  return a replacement value
     * @param int             $limit    the maximum number of replacements of each match in a
     *                                  single element (`-1` corresponds to no limit)
     * @param null|int        $count    the total number of all the replacements performed in
     *                                  in all the elements that matched
     *
     * @throws \UnexpectedValueException if any pattern fails to compile
     *
     * @return \Generator a generator whose values are the elements of the bytemap (unchanged if not
     *                    matching, after replacements otherwise) and whose keys are the indices of
     *                    those elements
     */
    public function pregReplaceCallback($pattern, callable $callback, int $limit = -1, ?int &$count = 0): \Generator;

    /**
     * `\preg_replace_callback_array` (generates all the elements, transforming matching values).
     *
     * @param iterable $patternsAndCallbacks an iterable whose keys are PCRE patterns and whose
     *                                       values are callbacks called whenever an element matches
     *                                       their corresponding pattern, which are passed an array
     *                                       of matches pertaining to that particular pattern and
     *                                       element and is expected to return a replacement value
     * @param int      $limit                the maximum number of replacements of each match in a
     *                                       single element (`-1` corresponds to no limit)
     * @param null|int $count                the total number of all the replacements performed in
     *                                       in all the elements that matched
     *
     * @throws \UnexpectedValueException if any pattern fails to compile
     *
     * @return \Generator a generator whose values are the elements of the bytemap (unchanged if not
     *                    matching, after replacements otherwise) and whose keys are the indices of
     *                    those elements
     */
    public function pregReplaceCallbackArray(iterable $patternsAndCallbacks, int $limit = -1, ?int &$count = 0): \Generator;

    // String API

    /**
     * `\implode` (joins bytemap elements with a string).
     *
     * @param string $glue a string that is to be inserted between the elements
     *
     * @return string a representation of all the bytemap elements in the same order,
     *                with the glue string between each element
     */
    public function implode(string $glue): string;

    /**
     * `\join` (an alias of `\implode`, joins bytemap elements with a string).
     *
     * @param string $glue a string that is to be inserted between the elements
     *
     * @return string a representation of all the bytemap elements in the same order,
     *                with the glue string between each element
     */
    public function join(string $glue): string;
}

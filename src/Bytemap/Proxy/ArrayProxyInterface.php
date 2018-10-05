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
    public static function wrap(BytemapInterface $bytemap): self;

    // Array conversion
    public function exportArray(): array;

    public static function import(string $defaultItem, iterable $iterms): self;

    // Array API
    public function chunk(int $size, bool $preserveKeys = false): \Generator;

    public function countValues(): array;

    public function filter(?callable $callback = null, int $flag = 0): \Generator;

    public function inArray(string $needle): bool;

    public function keyExists(int $key): bool;

    public function keyFirst(): ?int;

    public function keyLast(): ?int;

    public function keys(?string $searchValue = null): \Generator;

    public function map(?callable $callback, iterable ...$arguments): \Generator;

    public function merge(iterable ...$iterables): self;

    public function natCaseSort(): void;

    public function natSort(): void;

    public function pad(int $size, string $value): self;

    public function pop(): ?string;

    public function push(string ...$values): int;

    public function rand(int $num = 1);

    public function reduce(callable $callback, $initial = null);

    public function replace(iterable ...$iterables): self;

    public function reverse(): self;

    public function rSort(int $sortFlags = \SORT_REGULAR): void;

    public function search(string $needle);

    public function shift(): ?string;

    public function shuffle(): void;

    public function slice(int $offset, ?int $length = null): self;

    public function sort(int $sortFlags = \SORT_REGULAR): void;

    public function splice(int $offset, ?int $length = null, $replacement = []): self;

    /**
     * The equivalent of the array `+` operator.
     *
     * @param iterable ...$iterables
     *
     * @return self
     */
    public function union(iterable ...$iterables): self;

    public function unique(int $sortFlags = \SORT_STRING): \Generator;

    public function unshift(string ...$values): int;

    public function uSort(callable $valueCompareFunc): void;

    public function values(): \Generator;

    public function walk(callable $callback, $userdata = null): void;

    public static function combine(string $defaultItem, iterable $keys, iterable $values): self;

    /**
     * `array_fill` (fill an array with values).
     *
     * @param string  $defaultItem the default item of the underlying bytemap
     * @param int     $startIndex  the first index of the value used for filling
     * @param int     $num         number of elements to insert
     * @param ?string $value       value to use for filling.
     *                             `null` means that the default item will be used
     */
    public static function fill(string $defaultItem, int $startIndex, int $num, ?string $value = null): self;

    public static function fillKeys(string $defaultItem, iterable $keys, ?string $value = null);

    // PCRE API
    public function pregGrep(string $pattern, int $flags = 0): \Generator;

    // String API
    public function implode(): string;
}

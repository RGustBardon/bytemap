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

    public static function importArray(string $defaultItem, array $array): self;

    // Array API
    public function keyExists($key): bool;

    public function keyFirst(): ?int;

    public function keyLast(): ?int;

    public function keys($searchValue = null, bool $strict = false): array;

    /**
     * `array_fill` (fill an array with values).
     *
     * @param string $defaultItem the default item of the underlying bytemap
     * @param int    $startIndex  the first index of the value used for filling
     * @param int    $num         number of elements to insert
     * @param string $value       value to use for filling
     */
    public static function fill(string $defaultItem, int $startIndex, int $num, ?string $value = null): self;
}

<?php declare(strict_types=1);

/*
 * This file is part of the Bytemap package.
 *
 * (c) Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bytemap\Facade;

use Bytemap\Bytemap;
use Bytemap\BytemapInterface;

class ArrayFacade
{
    public static function fill(int $num, string $value): BytemapInterface
    {
        if ($num < 0) {
            throw new \OutOfRangeException('Number of elements can\'t be negative');
        }

        $bytemap = new Bytemap((string) $value);
        if ($num > 0) {
            $bytemap[$num - 1] = $value;
        }

        return $bytemap;
    }
}

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
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
final class BytemapPerformance
{
    /**
     * @revs(200)
     */
    public function benchNativeExpand(): void
    {
        $bytemap = new Bytemap("\x00");
        $elementCount = 0;
        for ($i = 0; $i < 30000; ++$i) {
            $index = $elementCount + \mt_rand(1, 100);
            $bytemap[$index] = "\x01";
            $elementCount = $index + 1;
        }
    }
}
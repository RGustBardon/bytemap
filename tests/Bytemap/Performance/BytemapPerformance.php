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
 * @BeforeMethods({"setUp"})
 *
 * @internal
 */
final class BytemapPerformance
{
    private $bytemap;

    private $lastIndex = 0;

    public function setUp(): void
    {
        \mt_srand(0);
        $this->bytemap = new Bytemap("\x0");
    }

    /**
     * @revs(100000)
     */
    public function benchNativeExpand(): void
    {
        $this->lastIndex += \mt_rand(1, 100);
        $this->bytemap[$this->lastIndex] = "\x1";
    }
}

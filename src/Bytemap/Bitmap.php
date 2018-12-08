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
 * An implementation of the `BytemapInterface` using a string to store Boolean values.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
class Bitmap extends Bytemap
{

    /** @var int */
    private $bitCount = 0;
    
    public function __construct()
    {
        parent::__construct("\x0");
    }
    
    public function offsetGet($index): bool
    {
        $parentIndex = $index >> 3;
        $parentElement = parent::offsetGet($parentIndex);
        return \ord($parentElement) & (1 << ($index % 8) - 1);
    }
    
}
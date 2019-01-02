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
trait BitShiftingTrait
{
    protected function shiftLeft(int $firstIndex, int $howMany): void {
        if (0 === ($firstIndex & 7)) {
            if ($firstIndex + $howMany >= $this->bitCount) {
                $this->map = \substr($this->map, 0, $firstIndex >> 3);
                $this->bitCount = $firstIndex;
                $this->elementCount = $firstIndex >> 3;
                $this->bytesInTotal = $this->elementCount;
                return;
            }
            if (0 === ($howMany & 7)) {
                $originalByteCount = \strlen($this->map);
                $this->map = \substr_replace($this->map, '', $firstIndex >> 3, $howMany >> 3);
                $byteCountDifference = $originalByteCount - \strlen($this->map);
                $this->bitCount -= $byteCountDifference << 3;
                $this->elementCount -= $byteCountDifference;
                $this->bytesInTotal -= $byteCountDifference;
                return;
            }
        }
        
        $targetStartBitIndex = $firstIndex;
        $sourceStartBitIndex = $firstIndex + $howMany;
        
        $maskFromLeft = [];
        for ($i = 0; $i <= 8; ++$i) {
            $maskFromLeft[$i] = \chr(\bindec(\str_pad(\str_repeat('1', $i), 8, '0', \STR_PAD_RIGHT)));
        }
        
        $maskFromRight = [];
        for ($i = 0; $i <= 8; ++$i) {
            $maskFromRight[$i] = \chr(\bindec(\str_repeat('1', $i)));
        }
        
        $lookupTableShiftLeft = [];
        for ($i = 0; $i < 8; ++$i) {
            $lookupTableShiftLeft[$i] = [];
            for ($j = 0; $j < 256; ++$j) {
                $lookupTableShiftLeft[$i][\chr($j)] = \chr($j << $i);
            }
        }
        
        $lookupTableShiftRight = [];
        for ($i = 0; $i < 8; ++$i) {
            $lookupTableShiftRight[$i] = [];
            for ($j = 0; $j < 256; ++$j) {
                $lookupTableShiftRight[$i][\chr($j)] = \chr($j >> $i);
            }
        }
        
        while ($sourceStartBitIndex < $this->bitCount) {
            $targetByteBitIndex = ($targetStartBitIndex & 7);
            $targetByteMissingCount = 8 - $targetByteBitIndex;
            
            $targetByteNewBits = "\x0";
            break;
        }
        
        $this->bitCount -= \min($howMany, $this->bitCount - $firstIndex);
        $this->map = \substr_replace($this->map, '', ($this->bitCount >> 3) + 1, \PHP_INT_MAX);
        $this->deriveProperties();
   }
    
}
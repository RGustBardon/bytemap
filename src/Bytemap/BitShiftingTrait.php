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
        
        $targetByteIndex = ($firstIndex >> 3);
        while ($sourceStartBitIndex < $this->bitCount) {
            if (isset($targetBitMissingCount)) {
                $targetBitMissingCount = 8;
            } else {
                $targetBitMissingCount = 8 - ($firstIndex & 7);
            }
            
            $sourceFirstByteIndex = ($sourceStartBitIndex >> 3);
            $sourceSecondByteIndex = $sourceFirstByteIndex + 1;
            
            
            $sourceFirstByte = $this->map[$sourceFirstByteIndex] ?? "\x0";
            $sourceSecondByte = $this->map[$sourceSecondByteIndex] ?? "\x0";
            
            $extractThisManyBitsFromFirstStartingRight = 8 - ($sourceStartBitIndex & 7);
            $shiftLeftFirstBy = $targetBitMissingCount - $extractThisManyBitsFromFirstStartingRight;
            
            $extractThisManyBitsFromSecondStartingLeft = $targetBitMissingCount - $extractThisManyBitsFromFirstStartingRight;
            $shiftRightSecondBy = 8 - $extractThisManyBitsFromSecondStartingLeft;
            
            $sourceFirstByte = ($sourceFirstByte & $maskFromLeft[$extractThisManyBitsFromFirstStartingRight]);
            if ($shiftLeftFirstBy > 0) {
                $sourceFirstByte = $lookupTableShiftRight[$shiftLeftFirstBy][$sourceFirstByte];
            } elseif ($shiftLeftFirstBy < 0) {
                $sourceFirstByte = $lookupTableShiftLeft[-$shiftLeftFirstBy][$sourceFirstByte];
            }
            
            $sourceSecondByte = ($sourceSecondByte & $maskFromRight[$extractThisManyBitsFromSecondStartingLeft]);
            if ($shiftRightSecondBy > 0) {
                $sourceSecondByte = $lookupTableShiftLeft[$shiftRightSecondBy][$sourceSecondByte];
            } elseif ($shiftRightSecondBy < 0) {
                $sourceSecondByte = $lookupTableShiftRight[-$shiftRightSecondBy][$sourceSecondByte];
            }
            
            $this->map[$targetByteIndex] = ($this->map[$targetByteIndex] & $maskFromRight[8 - $targetBitMissingCount]);
            $this->map[$targetByteIndex] = ($this->map[$targetByteIndex] | $sourceFirstByte);
            $this->map[$targetByteIndex] = ($this->map[$targetByteIndex] | $sourceSecondByte);

            ++$targetByteIndex;
            
            $sourceStartBitIndex += $targetBitMissingCount;
        }
        
        $this->bitCount -= \min($howMany, $this->bitCount - $firstIndex);
        $this->map = \substr_replace($this->map, '', ($this->bitCount >> 3) + 1, \PHP_INT_MAX);
        $this->deriveProperties();
   }
    
}
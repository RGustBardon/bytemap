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

    // `ArrayAccess`
    public function offsetExists($index): bool
    {
        return \is_int($index) && $index >= 0 && $index < $this->bitCount;
    }

    public function offsetGet($index): bool
    {
        static $mask = ["\x1", "\x2", "\x4", "\x8", "\x10", "\x20", "\x40", "\x80"];

        if (\is_int($index) && $index >= 0 && $index < $this->bitCount) {
            return "\x0" !== ($this->map[$index >> 3] & $mask[$index & 7]);
        }

        if (!\is_int($index)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type integer, '.\gettype($index).' given');
        }

        if (0 === $this->bitCount) {
            throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'The container is empty, so index '.$index.' does not exist');
        }

        throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Index out of range: '.$index.', expected 0 <= x <= '.($this->bitCount - 1));
    }

    public function offsetSet($index, $element): void
    {
        static $originalMask = ["\x1", "\x2", "\x4", "\x8", "\x10", "\x20", "\x40", "\x80"];
        static $invertedMask = ["\xfe", "\xfd", "\xfb", "\xf7", "\xef", "\xdf", "\xbf", "\x7f"];

        if (null === $index) {  // `$bitmap[] = $element`
            $index = $this->bitCount;
        }

        if (\is_int($index) && $index >= 0) {
            $byteIndex = $index >> 3;
            if (true === $element) {
                parent::offsetSet($byteIndex, ($this->map[$byteIndex] ?? "\x0") | $originalMask[$index & 7]);
            } elseif (false === $element) {
                parent::offsetSet($byteIndex, ($this->map[$byteIndex] ?? "\x0") & $invertedMask[$index & 7]);
            } else {
                throw new \TypeError(self::EXCEPTION_PREFIX.'Value must be a Boolean, '.\gettype($element).' given');
            }

            if ($index >= $this->bitCount) {
                $this->bitCount = $index + 1;
            }

            return;
        }
        if (!\is_int($index)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Index must be of type integer, '.\gettype($index).' given');
        }

        throw new \OutOfRangeException(self::EXCEPTION_PREFIX.'Negative index: '.$index);
    }

    public function offsetUnset($index): void
    {
        static $mask = ["\xff", "\xfe", "\xfc", "\xf8", "\xf0", "\xe0", "\xc0", "\x80"];
        static $shiftOneRight = [
            "\x0" => "\x0",
            "\x1" => "\x0",
            "\x2" => "\x1",
            "\x3" => "\x1",
            "\x4" => "\x2",
            "\x5" => "\x2",
            "\x6" => "\x3",
            "\x7" => "\x3",
            "\x8" => "\x4",
            "\x9" => "\x4",
            "\xa" => "\x5",
            "\xb" => "\x5",
            "\xc" => "\x6",
            "\xd" => "\x6",
            "\xe" => "\x7",
            "\xf" => "\x7",
            "\x10" => "\x8",
            "\x11" => "\x8",
            "\x12" => "\x9",
            "\x13" => "\x9",
            "\x14" => "\xa",
            "\x15" => "\xa",
            "\x16" => "\xb",
            "\x17" => "\xb",
            "\x18" => "\xc",
            "\x19" => "\xc",
            "\x1a" => "\xd",
            "\x1b" => "\xd",
            "\x1c" => "\xe",
            "\x1d" => "\xe",
            "\x1e" => "\xf",
            "\x1f" => "\xf",
            "\x20" => "\x10",
            "\x21" => "\x10",
            "\x22" => "\x11",
            "\x23" => "\x11",
            "\x24" => "\x12",
            "\x25" => "\x12",
            "\x26" => "\x13",
            "\x27" => "\x13",
            "\x28" => "\x14",
            "\x29" => "\x14",
            "\x2a" => "\x15",
            "\x2b" => "\x15",
            "\x2c" => "\x16",
            "\x2d" => "\x16",
            "\x2e" => "\x17",
            "\x2f" => "\x17",
            "\x30" => "\x18",
            "\x31" => "\x18",
            "\x32" => "\x19",
            "\x33" => "\x19",
            "\x34" => "\x1a",
            "\x35" => "\x1a",
            "\x36" => "\x1b",
            "\x37" => "\x1b",
            "\x38" => "\x1c",
            "\x39" => "\x1c",
            "\x3a" => "\x1d",
            "\x3b" => "\x1d",
            "\x3c" => "\x1e",
            "\x3d" => "\x1e",
            "\x3e" => "\x1f",
            "\x3f" => "\x1f",
            "\x40" => "\x20",
            "\x41" => "\x20",
            "\x42" => "\x21",
            "\x43" => "\x21",
            "\x44" => "\x22",
            "\x45" => "\x22",
            "\x46" => "\x23",
            "\x47" => "\x23",
            "\x48" => "\x24",
            "\x49" => "\x24",
            "\x4a" => "\x25",
            "\x4b" => "\x25",
            "\x4c" => "\x26",
            "\x4d" => "\x26",
            "\x4e" => "\x27",
            "\x4f" => "\x27",
            "\x50" => "\x28",
            "\x51" => "\x28",
            "\x52" => "\x29",
            "\x53" => "\x29",
            "\x54" => "\x2a",
            "\x55" => "\x2a",
            "\x56" => "\x2b",
            "\x57" => "\x2b",
            "\x58" => "\x2c",
            "\x59" => "\x2c",
            "\x5a" => "\x2d",
            "\x5b" => "\x2d",
            "\x5c" => "\x2e",
            "\x5d" => "\x2e",
            "\x5e" => "\x2f",
            "\x5f" => "\x2f",
            "\x60" => "\x30",
            "\x61" => "\x30",
            "\x62" => "\x31",
            "\x63" => "\x31",
            "\x64" => "\x32",
            "\x65" => "\x32",
            "\x66" => "\x33",
            "\x67" => "\x33",
            "\x68" => "\x34",
            "\x69" => "\x34",
            "\x6a" => "\x35",
            "\x6b" => "\x35",
            "\x6c" => "\x36",
            "\x6d" => "\x36",
            "\x6e" => "\x37",
            "\x6f" => "\x37",
            "\x70" => "\x38",
            "\x71" => "\x38",
            "\x72" => "\x39",
            "\x73" => "\x39",
            "\x74" => "\x3a",
            "\x75" => "\x3a",
            "\x76" => "\x3b",
            "\x77" => "\x3b",
            "\x78" => "\x3c",
            "\x79" => "\x3c",
            "\x7a" => "\x3d",
            "\x7b" => "\x3d",
            "\x7c" => "\x3e",
            "\x7d" => "\x3e",
            "\x7e" => "\x3f",
            "\x7f" => "\x3f",
            "\x80" => "\x40",
            "\x81" => "\x40",
            "\x82" => "\x41",
            "\x83" => "\x41",
            "\x84" => "\x42",
            "\x85" => "\x42",
            "\x86" => "\x43",
            "\x87" => "\x43",
            "\x88" => "\x44",
            "\x89" => "\x44",
            "\x8a" => "\x45",
            "\x8b" => "\x45",
            "\x8c" => "\x46",
            "\x8d" => "\x46",
            "\x8e" => "\x47",
            "\x8f" => "\x47",
            "\x90" => "\x48",
            "\x91" => "\x48",
            "\x92" => "\x49",
            "\x93" => "\x49",
            "\x94" => "\x4a",
            "\x95" => "\x4a",
            "\x96" => "\x4b",
            "\x97" => "\x4b",
            "\x98" => "\x4c",
            "\x99" => "\x4c",
            "\x9a" => "\x4d",
            "\x9b" => "\x4d",
            "\x9c" => "\x4e",
            "\x9d" => "\x4e",
            "\x9e" => "\x4f",
            "\x9f" => "\x4f",
            "\xa0" => "\x50",
            "\xa1" => "\x50",
            "\xa2" => "\x51",
            "\xa3" => "\x51",
            "\xa4" => "\x52",
            "\xa5" => "\x52",
            "\xa6" => "\x53",
            "\xa7" => "\x53",
            "\xa8" => "\x54",
            "\xa9" => "\x54",
            "\xaa" => "\x55",
            "\xab" => "\x55",
            "\xac" => "\x56",
            "\xad" => "\x56",
            "\xae" => "\x57",
            "\xaf" => "\x57",
            "\xb0" => "\x58",
            "\xb1" => "\x58",
            "\xb2" => "\x59",
            "\xb3" => "\x59",
            "\xb4" => "\x5a",
            "\xb5" => "\x5a",
            "\xb6" => "\x5b",
            "\xb7" => "\x5b",
            "\xb8" => "\x5c",
            "\xb9" => "\x5c",
            "\xba" => "\x5d",
            "\xbb" => "\x5d",
            "\xbc" => "\x5e",
            "\xbd" => "\x5e",
            "\xbe" => "\x5f",
            "\xbf" => "\x5f",
            "\xc0" => "\x60",
            "\xc1" => "\x60",
            "\xc2" => "\x61",
            "\xc3" => "\x61",
            "\xc4" => "\x62",
            "\xc5" => "\x62",
            "\xc6" => "\x63",
            "\xc7" => "\x63",
            "\xc8" => "\x64",
            "\xc9" => "\x64",
            "\xca" => "\x65",
            "\xcb" => "\x65",
            "\xcc" => "\x66",
            "\xcd" => "\x66",
            "\xce" => "\x67",
            "\xcf" => "\x67",
            "\xd0" => "\x68",
            "\xd1" => "\x68",
            "\xd2" => "\x69",
            "\xd3" => "\x69",
            "\xd4" => "\x6a",
            "\xd5" => "\x6a",
            "\xd6" => "\x6b",
            "\xd7" => "\x6b",
            "\xd8" => "\x6c",
            "\xd9" => "\x6c",
            "\xda" => "\x6d",
            "\xdb" => "\x6d",
            "\xdc" => "\x6e",
            "\xdd" => "\x6e",
            "\xde" => "\x6f",
            "\xdf" => "\x6f",
            "\xe0" => "\x70",
            "\xe1" => "\x70",
            "\xe2" => "\x71",
            "\xe3" => "\x71",
            "\xe4" => "\x72",
            "\xe5" => "\x72",
            "\xe6" => "\x73",
            "\xe7" => "\x73",
            "\xe8" => "\x74",
            "\xe9" => "\x74",
            "\xea" => "\x75",
            "\xeb" => "\x75",
            "\xec" => "\x76",
            "\xed" => "\x76",
            "\xee" => "\x77",
            "\xef" => "\x77",
            "\xf0" => "\x78",
            "\xf1" => "\x78",
            "\xf2" => "\x79",
            "\xf3" => "\x79",
            "\xf4" => "\x7a",
            "\xf5" => "\x7a",
            "\xf6" => "\x7b",
            "\xf7" => "\x7b",
            "\xf8" => "\x7c",
            "\xf9" => "\x7c",
            "\xfa" => "\x7d",
            "\xfb" => "\x7d",
            "\xfc" => "\x7e",
            "\xfd" => "\x7e",
            "\xfe" => "\x7f",
            "\xff" => "\x7f",
        ];

        if (\is_int($index) && $index >= 0 && $index < $this->bitCount) {
            $bitIndex = ($index & 7);

            if (--$this->bitCount === $index && 0 === $bitIndex) {
                --$this->bytesInTotal;
                --$this->elementCount;
                $this->map = \substr($this->map, 0, -1);
            } else {
                $carry = "\x0";
                for ($i = $this->elementCount - 1, $byteIndex = ($index >> 3); $i > $byteIndex; --$i) {
                    $this->map[$i] = ($shiftOneRight[$byte = $this->map[$i]] | $carry);
                    $carry = ("\x1" === ($byte & "\x1") ? "\x80" : "\x0");
                }

                // https://graphics.stanford.edu/~seander/bithacks.html#MaskedMerge
                $byte = $this->map[$i];
                $this->map[$i] = $byte ^ (($byte ^ ($shiftOneRight[$byte] | $carry)) & $mask[$bitIndex]);
            }
        }
    }
    
    // `Countable`
    public function count(): int
    {
        return $this->bitCount;
    }
}

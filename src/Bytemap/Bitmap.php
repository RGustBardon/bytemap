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
    protected const DEFAULT_BYTEMAP_VALUE = "\x0";

    /** @var int */
    protected /* int */ $bitCount = 0;

    public function __construct()
    {
        parent::__construct(self::DEFAULT_BYTEMAP_VALUE);
    }

    // `ArrayAccess`
    public function offsetExists($index): bool
    {
        return \is_int($index) && $index >= 0 && $index < $this->bitCount;
    }

    public function offsetGet($index) // : bool
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
            if (--$this->bitCount > $index) {
                $carry = "\x0";
                for ($i = $this->elementCount - 1, $byteIndex = ($index >> 3); $i > $byteIndex; --$i) {
                    $this->map[$i] = ($shiftOneRight[$byte = $this->map[$i]] | $carry);
                    $carry = ("\x1" === ($byte & "\x1") ? "\x80" : "\x0");
                }

                // https://graphics.stanford.edu/~seander/bithacks.html#MaskedMerge
                $byte = $this->map[$i];
                $this->map[$i] = $byte ^ (($byte ^ ($shiftOneRight[$byte] | $carry)) & $mask[$index & 7]);
            }

            if (0 === ($this->bitCount & 7)) {
                --$this->bytesInTotal;
                --$this->elementCount;
                $this->map = \substr($this->map, 0, -1);
            }
        }
    }

    // `Countable`
    public function count(): int
    {
        return $this->bitCount;
    }

    // `IteratorAggregate`
    public function getIterator(): \Traversable
    {
        static $mask = ["\x1", "\x2", "\x4", "\x8", "\x10", "\x20", "\x40", "\x80"];

        $map = $this->map;
        for ($bitIndex = 0, $byteIndex = 0, $lastByteIndex = $this->elementCount - 1; $byteIndex < $lastByteIndex; ++$byteIndex) {
            $byte = $map[$byteIndex];
            yield $bitIndex++ => "\x0" !== ($byte & "\x01");
            yield $bitIndex++ => "\x0" !== ($byte & "\x02");
            yield $bitIndex++ => "\x0" !== ($byte & "\x04");
            yield $bitIndex++ => "\x0" !== ($byte & "\x08");
            yield $bitIndex++ => "\x0" !== ($byte & "\x10");
            yield $bitIndex++ => "\x0" !== ($byte & "\x20");
            yield $bitIndex++ => "\x0" !== ($byte & "\x40");
            yield $bitIndex++ => "\x0" !== ($byte & "\x80");
        }

        if ($lastByteIndex >= 0) {
            for ($bit = 0, $byte = $map[$lastByteIndex], $bitCount = $this->bitCount; $bitIndex < $bitCount; ++$bitIndex, ++$bit) {
                yield $bitIndex => "\x0" !== ($byte & $mask[$bit]);
            }
        }
    }

    // `JsonSerializable`
    public function jsonSerialize(): array
    {
        return \iterator_to_array($this);
    }

    // `Serializable`
    public function serialize(): string
    {
        return \serialize([$this->bitCount, $this->map]);
    }

    // `BytemapInterface`
    public function find(
        ?iterable $elements = null,
        bool $whitelist = true,
        int $howMany = \PHP_INT_MAX,
        ?int $startAfter = null
        ): \Generator {
        static $mask = ["\x1", "\x2", "\x4", "\x8", "\x10", "\x20", "\x40", "\x80"];

        if (0 === $howMany) {
            return;
        }

        $howManyToSkip = $this->calculateHowManyToSkip($howMany > 0, $startAfter);
        if (null === $howManyToSkip) {
            return;
        }

        if (null === $elements) {
            $needles = [false => true];
            $whitelist = !$whitelist;
        } else {
            $needles = [];
            foreach ($elements as $value) {
                if (\is_bool($value)) {
                    $needles[$value] = true;
                }
            }
        }

        $allInclusive = 2 === \count($needles);
        if ($whitelist && $allInclusive || !$whitelist && !$needles) {
            $map = $this->map;
            if ($howMany > 0) {
                $bitCount = $this->bitCount;
                $elementCount = $this->elementCount;
                $bitIndex = $howManyToSkip;
                for ($byteIndex = $bitIndex >> 3; $byteIndex < $elementCount; ++$byteIndex) {
                    for ($bit = $bitIndex & 7, $byte = $map[$byteIndex]; $bitIndex < $bitCount && $bit < 8; ++$bitIndex, ++$bit) {
                        yield $bitIndex => "\x0" !== ($byte & $mask[$bit]);
                        if (0 === --$howMany) {
                            return;
                        }
                    }
                }
            } else {
                $bitCount = $this->bitCount;
                $bitIndex = $bitCount - 1 - $howManyToSkip;
                for ($byteIndex = $bitIndex >> 3; $byteIndex >= 0; --$byteIndex) {
                    for ($bit = $bitIndex & 7, $byte = $map[$byteIndex]; $bit >= 0; --$bitIndex, --$bit) {
                        yield $bitIndex => "\x0" !== ($byte & $mask[$bit]);
                        if (0 === ++$howMany) {
                            return;
                        }
                    }
                }
            }
        } elseif (!($whitelist && !$needles || !$whitelist && $allInclusive)) {
            // FIXME(rgustbardon): Return either `false` or `true` elements, honoring `$howMany` and `$startAfter`.
            yield from [];
        }

    }

    public function grep(iterable $patterns, bool $whitelist = true, int $howMany = \PHP_INT_MAX, ?int $startAfter = null): \Generator
    {
        // Both `Bitmap` should not extend `Bytemap`.
        // Instead, their interfaces should extend a generic interface that does not include `grep`.
        // `BytemapInterface` would then add `grep` for `Bytemap` only.
        // This issue will be resolved in the next version of the API.
        throw new \LogicException('Grepping makes no sense when dealing with bits.');
    }

    public function insert(iterable $elements, int $firstIndex = -1): void
    {
        // Prepare a substring to insert.
        $substringToInsert = '';
        $howManyBitsToInsert = 0;
        $byte = 0;
        foreach ($elements as $element) {
            if (true === $element) {
                $byte = ($byte | (1 << ($howManyBitsToInsert & 7)));
            } elseif (false !== $element) {
                throw new \TypeError(self::EXCEPTION_PREFIX.'Value must be a Boolean, '.\gettype($element).' given');
            }
            ++$howManyBitsToInsert;
            if (0 === ($howManyBitsToInsert & 7)) {
                $substringToInsert .= \chr($byte);
                $byte = 0;
            }
        }
        if (($howManyBitsToInsert & 7) > 0) {
            $substringToInsert .= \chr($byte);
        }

        // Insert the elements.

        // Conceptually, after the insertion, the string will consist of at most three different substrings.
        // Elements might already exist in the bitmap. These will be denoted by E.
        // New elements might need to be inserted. These will be denoted by N.
        // There might be a gap between existing elements and new elements.
        // It will be filled with zeros and denoted by G.
        // The question mark means that the substring is optional.

        // Substrings will be concatenated quickly, and then the `delete` method will remove all the
        // superfluous bits. For instance, if the bitmap contains 3 bits and 2 bits are to be inserted with
        // their first index being 10 (0xa), then:

        // Indices:          0123|4567 89ab|cdef 0123|4567
        // To be inserted:   NN
        // Original bitmap:  EEE0|0000
        // `$firstIndex`:                ^
        // Concatenation:    EEE0|0000 GGGG|GGGG NN00|0000
        // Superfluous bits:    ^ ^^^^         ^   ^^ ^^^^
        // Deletion:         EEEG|GGGG GGNN|0000

        // The above is a simplified view. In reality, the bits are reversed in each byte:

        // Indices:          7654|3210 fedc|ba98 7654|3210
        // To be inserted:   NN
        // Original bitmap:  0000|0EEE
        // Concatenation:    0000|0EEE GGGG|GGGG 0000|00NN
        // Deletion:         GGGG|GEEE 0000|NNGG

        // If `$firstIndex` is out of bounds (for instance, in case there are originally 3 bits, -4 or 3
        // would be an out-of-bound first index) and no elements are to be inserted, then the bitmap
        // will still be mutated: it will be padded with zeros in the direction where elements would
        // have been inserted.
        if (-1 === $firstIndex || $firstIndex > $this->bitCount - 1) {
            // Zero or more elements are to be inserted after the existing elements (X?G?N?).
            $originalBitCount = $this->bitCount;
            $tailRelativeBitIndex = ($this->bitCount & 7);

            // Calculate if a gap should exist between the existing elements and the new ones.
            $gapInBits = \max(0, $firstIndex - $this->bitCount);
            $gapInBytes = ($gapInBits >> 3) + (0 === ($gapInBits & 7) ? 0 : 1);

            if ($gapInBytes > 0) {
                // Append the gap (X?GN?).
                $this->map .= \str_repeat("\x0", $gapInBytes);
                $this->deriveProperties();
                $this->bitCount = ($this->elementCount << 3);
                $this->delete($originalBitCount + $gapInBits);
            }

            if ($howManyBitsToInsert > 0) {
                // Append new elements (X?G?N).
                $bitCountAfterFillingTheGap = $this->bitCount;
                $tailRelativeBitIndex = ($this->bitCount & 7);

                $this->map .= $substringToInsert;
                $this->deriveProperties();
                $this->bitCount = ($this->elementCount << 3);

                if ($tailRelativeBitIndex > 0) {
                    // The gap did not end at a full byte, so remove the superfluous bits.
                    $this->delete($bitCountAfterFillingTheGap, 8 - $tailRelativeBitIndex);
                }

                // Delete all the bits after the last inserted bit.
                $this->delete($originalBitCount + $gapInBits + $howManyBitsToInsert);
            }
        } else {
            // Elements are to be inserted left of the rightmost bit though not necessarily immediately before it.

            $originalFirstIndex = $firstIndex;
            // Calculate the positive index corresponding to the negative one.
            if ($firstIndex < 0) {
                $firstIndex += $this->bitCount;

                // Keep the indices within the bounds.
                if ($firstIndex < 0) {
                    $firstIndex = 0;
                }
            }

            $newBitCount = $this->bitCount + $howManyBitsToInsert;
            if (-$originalFirstIndex > $newBitCount) {
                // Resize the bitmap if the negative first bit index is greater than the new bit count (N?GX?).
                $originalBitCount = $this->bitCount;
                $overflowInBits = -$originalFirstIndex - $newBitCount - ($howManyBitsToInsert > 0 ? 0 : 1);
                $padLengthInBits = $overflowInBits + $howManyBitsToInsert;
                $padLengthInBytes = (($padLengthInBits + 7) >> 3);
                $substringToInsert = \str_pad($substringToInsert, $padLengthInBytes, "\x0", \STR_PAD_RIGHT);

                $this->map = $substringToInsert.$this->map;
                $this->deriveProperties();
                $this->bitCount += ($padLengthInBytes << 3);
                if (($padLengthInBits & 7) > 0) {
                    // The gap did not end at a full byte, so remove the superfluous bits.
                    $this->delete($padLengthInBits, 8 - ($padLengthInBits & 7));
                }
            } elseif ($howManyBitsToInsert > 0) {
                // There will be no gap left or right of the original bitmap (X?NX).

                if (0 === ($firstIndex & 7)) {
                    // The bits are to be inserted at a full byte.
                    if ($firstIndex > 0) {
                        // The bits are not to be inserted at the beginning, so splice (XNX).
                        $this->map = \substr($this->map, 0, $firstIndex >> 3).$substringToInsert.\substr($this->map, $firstIndex >> 3);
                    } else {
                        // The bits are to be inserted add the beginning, so prepend (NX).
                        $this->map = $substringToInsert.$this->map;
                    }
                    $this->deriveProperties();
                    $this->bitCount += (\strlen($substringToInsert) << 3);
                    if (($howManyBitsToInsert & 7) > 0) {
                        // The inserted bits did not end at a full byte, so remove the superfluous bits.
                        $this->delete($firstIndex + $howManyBitsToInsert, 8 - ($howManyBitsToInsert & 7));
                    }
                } else {
                    // Splice inside a byte (XNX).

                    // The part of the original bytemap to the left of what is being inserted will be
                    // referred to as 'head,' the part to the right will be referred to as 'tail.'

                    // Since splicing does not start at a full byte, both the head and the tail will
                    // originally have one byte in common. The overlapping bits (rightmost in the head
                    // and leftmost in the tail) will then by removed by calling the `delete` method.

                    // Head bits will be denoted as H, tail bits will be denoted as T.
                    // For instance, if the bitmap contains 20 bits and 5 bits are to be inserted with
                    // their first index being 10 (0xa), then:

                    // Indices:         0123|4567 89ab|cdef 0123|4567 89ab|cdef 0123|4567
                    // To be inserted:  NNNNN
                    // Original bitmap: EEEE|EEEE EEEE|EEEE EEEE|0000
                    //                            ---------
                    //                                |     same byte
                    //                                |------------------.
                    //                                |                   \
                    //                            ---------           ---------
                    // Concatenation:   HHHH|HHHH HHHH|HHHH NNNN|N000 TTTT|TTTT TTTT|0000
                    // `$firstIndex`:               ^
                    // Overlapping bits:            ^^ ^^^^           ^^
                    // 1st deletion:                                                 ^^^^
                    // 2nd deletion:                              ^^^ ^^                  ('middle gap')
                    // 3rd deletion:                ^^ ^^^^
                    // Result:          HHHH|HHHH HHNN|NNNT TTTT|TTTT T000|0000

                    // The above is a simplified view. In reality, the bits are reversed in each byte:

                    // Indices:         7654|3210 fedc|ba98 7654|3210 fedc|ba98 7654|3210
                    // To be inserted:  NNNNN
                    // Original bitmap: EEEE|EEEE EEEE|EEEE 0000|EEEE
                    //                            ---------
                    //                                |     same byte
                    //                                |------------------.
                    //                                |                   \
                    //                            ---------           ---------
                    // Concatenation:   HHHH|HHHH HHHH|HHHH 000N|NNNN TTTT|TTTT 0000|TTTT
                    // Result:          HHHH|HHHH TNNN|NNHH TTTT|TTTT 0000|000T
                    $originalBitCount = $this->bitCount;
                    $head = \substr($this->map, 0, ($firstIndex >> 3) + 1);
                    $tail = \substr($this->map, $firstIndex >> 3);
                    $this->map = $head.$substringToInsert.$tail;
                    $this->deriveProperties();
                    $this->bitCount = ($this->elementCount << 3);
                    if (($originalBitCount & 7) > 0) {
                        // The tail did not end at a full byte, so remove the superfluous bits.
                        $this->delete(($originalBitCount & 7) - 8);
                    }
                    // Remove the middle gap.
                    $middleGapLengthInBits = ($firstIndex & 7);
                    if (($howManyBitsToInsert & 7) > 0) {
                        $middleGapLengthInBits += 8 - ($howManyBitsToInsert & 7);
                    }
                    $this->delete((\strlen($head) << 3) + $howManyBitsToInsert, $middleGapLengthInBits);
                    // The head did not end at a full byte, so remove the superfluous bits.
                    $this->delete($firstIndex, 8 - ($firstIndex & 7));
                }
            }
        }
    }

    public function delete(int $firstIndex = -1, int $howMany = \PHP_INT_MAX): void
    {
        // Calculate the positive index corresponding to the negative one.
        if ($firstIndex < 0) {
            $firstIndex += $this->bitCount;
        }

        // If we still end up with a negative index, decrease `$howMany`.
        if ($firstIndex < 0) {
            $howMany += $firstIndex;
            $firstIndex = 0;
        }

        // Check if there is anything to delete or if the positive index is out of bounds.
        if ($howMany < 1 || 0 === $this->bitCount || $firstIndex >= $this->bitCount) {
            return;
        }

        // Delete the elements.

        // Bit-shifting a substring is expensive, so delete all the full bytes in the range, for example:
        //
        // Indices:            0123|4567 89ab|cdef 0123|4567 89ab|cdef 0123|4567 89ab|cdef
        // To be deleted:             XX XXXX|XXXX XXXX|XXXX XXXX XXX
        // Full bytes:                   ^^^^ ^^^^ ^^^^ ^^^^

        $firstFullByteIndex = ($firstIndex >> 3) + ((0 === $firstIndex & 7) ? 0 : 1);
        $howManyFullBytes = \min($this->elementCount - 1, ($firstIndex + $howMany) >> 3) - $firstFullByteIndex;
        if ($howManyFullBytes > 0) {
            parent::deleteWithNonNegativeIndex($firstFullByteIndex, $howManyFullBytes, $this->elementCount);
            $deletedBitCount = ($howManyFullBytes << 3);
            $this->bitCount -= $deletedBitCount;
            $howMany -= $deletedBitCount;
            if (0 === $howMany) {
                return;
            }
        }

        if (0 === $firstIndex & 7 && $firstIndex + $howMany >= $this->bitCount) {
            // If the first index conceptually begins a byte and everything to its right is to be deleted,
            // no bit-shifting is necessary.

            $this->map = \substr($this->map, 0, $firstIndex >> 3);
            $this->bitCount = $firstIndex;
            $this->elementCount = $firstIndex >> 3;
            $this->bytesInTotal = $this->elementCount;

            return;
        }

        // Keep rewriting the target with assembled bytes.

        // During the first iteration, the assembled byte will include some target bits.
        // After the first iteration, all the assembled bytes will consist of source bits only.

        // Conceptually:

        // Indices:            0123|4567 89ab|cdef 0123|4567 89ab|cdef
        // To be deleted:             XX XXXX XXX
        // Source bits:                          ^ ^^^^ ^^^^ ^^^^ ^^^^
        // Target bits:               ^^ ^^^^ ^^^^ ^^^^ ^^^
        // 1st assembled byte: ^^^^ ^^           ^ ^                   (includes six target bits)
        // 2nd assembled byte: 1111|1111            ^^^ ^^^^ ^         (consists entirely of source bits)
        // 3rd assembled byte: 1111|1111 2222|2222            ^^^ ^^^^ (consists of source bits and zeros)

        // The above is a simplified view. In reality, the bits are reversed in each byte:

        // Indices:            7654|3210 fedc|ba98 7654|3210 fedc|ba98
        // To be deleted:      XX         XXX XXXX
        // Source bits:                  ^         ^^^^ ^^^^ ^^^^ ^^^^
        // Target bits:        ^^        ^^^^ ^^^^  ^^^ ^^^^
        // 1st assembled byte:   ^^^^ ^^ ^                 ^           (includes six target bits)
        // 2nd assembled byte: 1111|1111           ^^^^ ^^^          ^ (consists entirely of source bits)
        // 3rd assembled byte: 1111|1111 2222|2222           ^^^^ ^^^  (consists of source bits and zeros)

        $lastByteIndex = $this->elementCount - 1;
        $bitCount = $this->bitCount;
        $map = $this->map;

        $targetHeadBitAbsoluteIndex = $firstIndex;
        $sourceHeadBitAbsoluteIndex = $firstIndex + $howMany;

        while ($sourceHeadBitAbsoluteIndex < $bitCount) {
            // Find out how many target bits are needed to assemble a byte.
            $targetHeadBitRelativeBitIndex = $targetHeadBitAbsoluteIndex & 7;
            $targetByteMissingBitCount = 8 - $targetHeadBitRelativeBitIndex;

            // Get the current source byte as an integer (bit-shifting operators do not work for strings).
            $sourceHeadByteIndex = $sourceHeadBitAbsoluteIndex >> 3;
            $assembledByte = \ord($map[$sourceHeadByteIndex]);

            $sourceHeadShift = $sourceHeadBitAbsoluteIndex & 7;
            if ($sourceHeadShift > 0) {
                // Shift the source bits to be copied to the end of the assembled byte.
                $assembledByte >>= $sourceHeadShift;
                $sourceAssembledBitCount = 8 - $sourceHeadShift;
                if ($sourceAssembledBitCount < $targetByteMissingBitCount && $sourceHeadByteIndex < $lastByteIndex) {
                    // There are not enough bits in the assembled byte, so augment it with the next source byte.
                    $assembledByte |= (
                            \ord($map[$sourceHeadByteIndex + 1])
                            & 0xff >> (8 - $targetByteMissingBitCount + $sourceAssembledBitCount)
                        ) << $sourceAssembledBitCount;
                }
            }

            $targetHeadByteIndex = $targetHeadBitAbsoluteIndex >> 3;
            if ($targetHeadBitRelativeBitIndex > 0) {
                // Some of the bits of the target byte need to be preserved, so augment the assembled byte.
                $assembledByte =
                    \ord($map[$targetHeadByteIndex])
                    & 0xff >> $targetByteMissingBitCount
                    | $assembledByte << $targetHeadBitRelativeBitIndex;
            }

            // Overwrite the target byte with the assembled byte.
            $map[$targetHeadByteIndex] = \chr($assembledByte);

            // Advance by the number of bits rewritten.
            $targetHeadBitAbsoluteIndex += $targetByteMissingBitCount;
            $sourceHeadBitAbsoluteIndex += $targetByteMissingBitCount;
        }

        $this->bitCount -= \min($howMany, $bitCount - $firstIndex);
        // Remove all the bytes after the last rewritten byte.
        $this->map = \substr_replace($map, '', ($this->bitCount >> 3) + 1, \PHP_INT_MAX);
        $this->deriveProperties();
    }

    public function streamJson($stream): void
    {
        static $byteMapping = [
            "\x0" => 'false,false,false,false,false,false,false,false,',
            "\x1" => 'true,false,false,false,false,false,false,false,',
            "\x2" => 'false,true,false,false,false,false,false,false,',
            "\x3" => 'true,true,false,false,false,false,false,false,',
            "\x4" => 'false,false,true,false,false,false,false,false,',
            "\x5" => 'true,false,true,false,false,false,false,false,',
            "\x6" => 'false,true,true,false,false,false,false,false,',
            "\x7" => 'true,true,true,false,false,false,false,false,',
            "\x8" => 'false,false,false,true,false,false,false,false,',
            "\x9" => 'true,false,false,true,false,false,false,false,',
            "\xa" => 'false,true,false,true,false,false,false,false,',
            "\xb" => 'true,true,false,true,false,false,false,false,',
            "\xc" => 'false,false,true,true,false,false,false,false,',
            "\xd" => 'true,false,true,true,false,false,false,false,',
            "\xe" => 'false,true,true,true,false,false,false,false,',
            "\xf" => 'true,true,true,true,false,false,false,false,',
            "\x10" => 'false,false,false,false,true,false,false,false,',
            "\x11" => 'true,false,false,false,true,false,false,false,',
            "\x12" => 'false,true,false,false,true,false,false,false,',
            "\x13" => 'true,true,false,false,true,false,false,false,',
            "\x14" => 'false,false,true,false,true,false,false,false,',
            "\x15" => 'true,false,true,false,true,false,false,false,',
            "\x16" => 'false,true,true,false,true,false,false,false,',
            "\x17" => 'true,true,true,false,true,false,false,false,',
            "\x18" => 'false,false,false,true,true,false,false,false,',
            "\x19" => 'true,false,false,true,true,false,false,false,',
            "\x1a" => 'false,true,false,true,true,false,false,false,',
            "\x1b" => 'true,true,false,true,true,false,false,false,',
            "\x1c" => 'false,false,true,true,true,false,false,false,',
            "\x1d" => 'true,false,true,true,true,false,false,false,',
            "\x1e" => 'false,true,true,true,true,false,false,false,',
            "\x1f" => 'true,true,true,true,true,false,false,false,',
            "\x20" => 'false,false,false,false,false,true,false,false,',
            "\x21" => 'true,false,false,false,false,true,false,false,',
            "\x22" => 'false,true,false,false,false,true,false,false,',
            "\x23" => 'true,true,false,false,false,true,false,false,',
            "\x24" => 'false,false,true,false,false,true,false,false,',
            "\x25" => 'true,false,true,false,false,true,false,false,',
            "\x26" => 'false,true,true,false,false,true,false,false,',
            "\x27" => 'true,true,true,false,false,true,false,false,',
            "\x28" => 'false,false,false,true,false,true,false,false,',
            "\x29" => 'true,false,false,true,false,true,false,false,',
            "\x2a" => 'false,true,false,true,false,true,false,false,',
            "\x2b" => 'true,true,false,true,false,true,false,false,',
            "\x2c" => 'false,false,true,true,false,true,false,false,',
            "\x2d" => 'true,false,true,true,false,true,false,false,',
            "\x2e" => 'false,true,true,true,false,true,false,false,',
            "\x2f" => 'true,true,true,true,false,true,false,false,',
            "\x30" => 'false,false,false,false,true,true,false,false,',
            "\x31" => 'true,false,false,false,true,true,false,false,',
            "\x32" => 'false,true,false,false,true,true,false,false,',
            "\x33" => 'true,true,false,false,true,true,false,false,',
            "\x34" => 'false,false,true,false,true,true,false,false,',
            "\x35" => 'true,false,true,false,true,true,false,false,',
            "\x36" => 'false,true,true,false,true,true,false,false,',
            "\x37" => 'true,true,true,false,true,true,false,false,',
            "\x38" => 'false,false,false,true,true,true,false,false,',
            "\x39" => 'true,false,false,true,true,true,false,false,',
            "\x3a" => 'false,true,false,true,true,true,false,false,',
            "\x3b" => 'true,true,false,true,true,true,false,false,',
            "\x3c" => 'false,false,true,true,true,true,false,false,',
            "\x3d" => 'true,false,true,true,true,true,false,false,',
            "\x3e" => 'false,true,true,true,true,true,false,false,',
            "\x3f" => 'true,true,true,true,true,true,false,false,',
            "\x40" => 'false,false,false,false,false,false,true,false,',
            "\x41" => 'true,false,false,false,false,false,true,false,',
            "\x42" => 'false,true,false,false,false,false,true,false,',
            "\x43" => 'true,true,false,false,false,false,true,false,',
            "\x44" => 'false,false,true,false,false,false,true,false,',
            "\x45" => 'true,false,true,false,false,false,true,false,',
            "\x46" => 'false,true,true,false,false,false,true,false,',
            "\x47" => 'true,true,true,false,false,false,true,false,',
            "\x48" => 'false,false,false,true,false,false,true,false,',
            "\x49" => 'true,false,false,true,false,false,true,false,',
            "\x4a" => 'false,true,false,true,false,false,true,false,',
            "\x4b" => 'true,true,false,true,false,false,true,false,',
            "\x4c" => 'false,false,true,true,false,false,true,false,',
            "\x4d" => 'true,false,true,true,false,false,true,false,',
            "\x4e" => 'false,true,true,true,false,false,true,false,',
            "\x4f" => 'true,true,true,true,false,false,true,false,',
            "\x50" => 'false,false,false,false,true,false,true,false,',
            "\x51" => 'true,false,false,false,true,false,true,false,',
            "\x52" => 'false,true,false,false,true,false,true,false,',
            "\x53" => 'true,true,false,false,true,false,true,false,',
            "\x54" => 'false,false,true,false,true,false,true,false,',
            "\x55" => 'true,false,true,false,true,false,true,false,',
            "\x56" => 'false,true,true,false,true,false,true,false,',
            "\x57" => 'true,true,true,false,true,false,true,false,',
            "\x58" => 'false,false,false,true,true,false,true,false,',
            "\x59" => 'true,false,false,true,true,false,true,false,',
            "\x5a" => 'false,true,false,true,true,false,true,false,',
            "\x5b" => 'true,true,false,true,true,false,true,false,',
            "\x5c" => 'false,false,true,true,true,false,true,false,',
            "\x5d" => 'true,false,true,true,true,false,true,false,',
            "\x5e" => 'false,true,true,true,true,false,true,false,',
            "\x5f" => 'true,true,true,true,true,false,true,false,',
            "\x60" => 'false,false,false,false,false,true,true,false,',
            "\x61" => 'true,false,false,false,false,true,true,false,',
            "\x62" => 'false,true,false,false,false,true,true,false,',
            "\x63" => 'true,true,false,false,false,true,true,false,',
            "\x64" => 'false,false,true,false,false,true,true,false,',
            "\x65" => 'true,false,true,false,false,true,true,false,',
            "\x66" => 'false,true,true,false,false,true,true,false,',
            "\x67" => 'true,true,true,false,false,true,true,false,',
            "\x68" => 'false,false,false,true,false,true,true,false,',
            "\x69" => 'true,false,false,true,false,true,true,false,',
            "\x6a" => 'false,true,false,true,false,true,true,false,',
            "\x6b" => 'true,true,false,true,false,true,true,false,',
            "\x6c" => 'false,false,true,true,false,true,true,false,',
            "\x6d" => 'true,false,true,true,false,true,true,false,',
            "\x6e" => 'false,true,true,true,false,true,true,false,',
            "\x6f" => 'true,true,true,true,false,true,true,false,',
            "\x70" => 'false,false,false,false,true,true,true,false,',
            "\x71" => 'true,false,false,false,true,true,true,false,',
            "\x72" => 'false,true,false,false,true,true,true,false,',
            "\x73" => 'true,true,false,false,true,true,true,false,',
            "\x74" => 'false,false,true,false,true,true,true,false,',
            "\x75" => 'true,false,true,false,true,true,true,false,',
            "\x76" => 'false,true,true,false,true,true,true,false,',
            "\x77" => 'true,true,true,false,true,true,true,false,',
            "\x78" => 'false,false,false,true,true,true,true,false,',
            "\x79" => 'true,false,false,true,true,true,true,false,',
            "\x7a" => 'false,true,false,true,true,true,true,false,',
            "\x7b" => 'true,true,false,true,true,true,true,false,',
            "\x7c" => 'false,false,true,true,true,true,true,false,',
            "\x7d" => 'true,false,true,true,true,true,true,false,',
            "\x7e" => 'false,true,true,true,true,true,true,false,',
            "\x7f" => 'true,true,true,true,true,true,true,false,',
            "\x80" => 'false,false,false,false,false,false,false,true,',
            "\x81" => 'true,false,false,false,false,false,false,true,',
            "\x82" => 'false,true,false,false,false,false,false,true,',
            "\x83" => 'true,true,false,false,false,false,false,true,',
            "\x84" => 'false,false,true,false,false,false,false,true,',
            "\x85" => 'true,false,true,false,false,false,false,true,',
            "\x86" => 'false,true,true,false,false,false,false,true,',
            "\x87" => 'true,true,true,false,false,false,false,true,',
            "\x88" => 'false,false,false,true,false,false,false,true,',
            "\x89" => 'true,false,false,true,false,false,false,true,',
            "\x8a" => 'false,true,false,true,false,false,false,true,',
            "\x8b" => 'true,true,false,true,false,false,false,true,',
            "\x8c" => 'false,false,true,true,false,false,false,true,',
            "\x8d" => 'true,false,true,true,false,false,false,true,',
            "\x8e" => 'false,true,true,true,false,false,false,true,',
            "\x8f" => 'true,true,true,true,false,false,false,true,',
            "\x90" => 'false,false,false,false,true,false,false,true,',
            "\x91" => 'true,false,false,false,true,false,false,true,',
            "\x92" => 'false,true,false,false,true,false,false,true,',
            "\x93" => 'true,true,false,false,true,false,false,true,',
            "\x94" => 'false,false,true,false,true,false,false,true,',
            "\x95" => 'true,false,true,false,true,false,false,true,',
            "\x96" => 'false,true,true,false,true,false,false,true,',
            "\x97" => 'true,true,true,false,true,false,false,true,',
            "\x98" => 'false,false,false,true,true,false,false,true,',
            "\x99" => 'true,false,false,true,true,false,false,true,',
            "\x9a" => 'false,true,false,true,true,false,false,true,',
            "\x9b" => 'true,true,false,true,true,false,false,true,',
            "\x9c" => 'false,false,true,true,true,false,false,true,',
            "\x9d" => 'true,false,true,true,true,false,false,true,',
            "\x9e" => 'false,true,true,true,true,false,false,true,',
            "\x9f" => 'true,true,true,true,true,false,false,true,',
            "\xa0" => 'false,false,false,false,false,true,false,true,',
            "\xa1" => 'true,false,false,false,false,true,false,true,',
            "\xa2" => 'false,true,false,false,false,true,false,true,',
            "\xa3" => 'true,true,false,false,false,true,false,true,',
            "\xa4" => 'false,false,true,false,false,true,false,true,',
            "\xa5" => 'true,false,true,false,false,true,false,true,',
            "\xa6" => 'false,true,true,false,false,true,false,true,',
            "\xa7" => 'true,true,true,false,false,true,false,true,',
            "\xa8" => 'false,false,false,true,false,true,false,true,',
            "\xa9" => 'true,false,false,true,false,true,false,true,',
            "\xaa" => 'false,true,false,true,false,true,false,true,',
            "\xab" => 'true,true,false,true,false,true,false,true,',
            "\xac" => 'false,false,true,true,false,true,false,true,',
            "\xad" => 'true,false,true,true,false,true,false,true,',
            "\xae" => 'false,true,true,true,false,true,false,true,',
            "\xaf" => 'true,true,true,true,false,true,false,true,',
            "\xb0" => 'false,false,false,false,true,true,false,true,',
            "\xb1" => 'true,false,false,false,true,true,false,true,',
            "\xb2" => 'false,true,false,false,true,true,false,true,',
            "\xb3" => 'true,true,false,false,true,true,false,true,',
            "\xb4" => 'false,false,true,false,true,true,false,true,',
            "\xb5" => 'true,false,true,false,true,true,false,true,',
            "\xb6" => 'false,true,true,false,true,true,false,true,',
            "\xb7" => 'true,true,true,false,true,true,false,true,',
            "\xb8" => 'false,false,false,true,true,true,false,true,',
            "\xb9" => 'true,false,false,true,true,true,false,true,',
            "\xba" => 'false,true,false,true,true,true,false,true,',
            "\xbb" => 'true,true,false,true,true,true,false,true,',
            "\xbc" => 'false,false,true,true,true,true,false,true,',
            "\xbd" => 'true,false,true,true,true,true,false,true,',
            "\xbe" => 'false,true,true,true,true,true,false,true,',
            "\xbf" => 'true,true,true,true,true,true,false,true,',
            "\xc0" => 'false,false,false,false,false,false,true,true,',
            "\xc1" => 'true,false,false,false,false,false,true,true,',
            "\xc2" => 'false,true,false,false,false,false,true,true,',
            "\xc3" => 'true,true,false,false,false,false,true,true,',
            "\xc4" => 'false,false,true,false,false,false,true,true,',
            "\xc5" => 'true,false,true,false,false,false,true,true,',
            "\xc6" => 'false,true,true,false,false,false,true,true,',
            "\xc7" => 'true,true,true,false,false,false,true,true,',
            "\xc8" => 'false,false,false,true,false,false,true,true,',
            "\xc9" => 'true,false,false,true,false,false,true,true,',
            "\xca" => 'false,true,false,true,false,false,true,true,',
            "\xcb" => 'true,true,false,true,false,false,true,true,',
            "\xcc" => 'false,false,true,true,false,false,true,true,',
            "\xcd" => 'true,false,true,true,false,false,true,true,',
            "\xce" => 'false,true,true,true,false,false,true,true,',
            "\xcf" => 'true,true,true,true,false,false,true,true,',
            "\xd0" => 'false,false,false,false,true,false,true,true,',
            "\xd1" => 'true,false,false,false,true,false,true,true,',
            "\xd2" => 'false,true,false,false,true,false,true,true,',
            "\xd3" => 'true,true,false,false,true,false,true,true,',
            "\xd4" => 'false,false,true,false,true,false,true,true,',
            "\xd5" => 'true,false,true,false,true,false,true,true,',
            "\xd6" => 'false,true,true,false,true,false,true,true,',
            "\xd7" => 'true,true,true,false,true,false,true,true,',
            "\xd8" => 'false,false,false,true,true,false,true,true,',
            "\xd9" => 'true,false,false,true,true,false,true,true,',
            "\xda" => 'false,true,false,true,true,false,true,true,',
            "\xdb" => 'true,true,false,true,true,false,true,true,',
            "\xdc" => 'false,false,true,true,true,false,true,true,',
            "\xdd" => 'true,false,true,true,true,false,true,true,',
            "\xde" => 'false,true,true,true,true,false,true,true,',
            "\xdf" => 'true,true,true,true,true,false,true,true,',
            "\xe0" => 'false,false,false,false,false,true,true,true,',
            "\xe1" => 'true,false,false,false,false,true,true,true,',
            "\xe2" => 'false,true,false,false,false,true,true,true,',
            "\xe3" => 'true,true,false,false,false,true,true,true,',
            "\xe4" => 'false,false,true,false,false,true,true,true,',
            "\xe5" => 'true,false,true,false,false,true,true,true,',
            "\xe6" => 'false,true,true,false,false,true,true,true,',
            "\xe7" => 'true,true,true,false,false,true,true,true,',
            "\xe8" => 'false,false,false,true,false,true,true,true,',
            "\xe9" => 'true,false,false,true,false,true,true,true,',
            "\xea" => 'false,true,false,true,false,true,true,true,',
            "\xeb" => 'true,true,false,true,false,true,true,true,',
            "\xec" => 'false,false,true,true,false,true,true,true,',
            "\xed" => 'true,false,true,true,false,true,true,true,',
            "\xee" => 'false,true,true,true,false,true,true,true,',
            "\xef" => 'true,true,true,true,false,true,true,true,',
            "\xf0" => 'false,false,false,false,true,true,true,true,',
            "\xf1" => 'true,false,false,false,true,true,true,true,',
            "\xf2" => 'false,true,false,false,true,true,true,true,',
            "\xf3" => 'true,true,false,false,true,true,true,true,',
            "\xf4" => 'false,false,true,false,true,true,true,true,',
            "\xf5" => 'true,false,true,false,true,true,true,true,',
            "\xf6" => 'false,true,true,false,true,true,true,true,',
            "\xf7" => 'true,true,true,false,true,true,true,true,',
            "\xf8" => 'false,false,false,true,true,true,true,true,',
            "\xf9" => 'true,false,false,true,true,true,true,true,',
            "\xfa" => 'false,true,false,true,true,true,true,true,',
            "\xfb" => 'true,true,false,true,true,true,true,true,',
            "\xfc" => 'false,false,true,true,true,true,true,true,',
            "\xfd" => 'true,false,true,true,true,true,true,true,',
            "\xfe" => 'false,true,true,true,true,true,true,true,',
            "\xff" => 'true,true,true,true,true,true,true,true,',
        ];

        self::ensureStream($stream);

        $buffer = '[';
        $index = 0;
        $map = $this->map;

        $batchSize = (self::BATCH_ELEMENT_COUNT >> 3);
        $bytesInTotalExceptLast = $this->bytesInTotal - 1;
        for ($index = 0; $index < $bytesInTotalExceptLast; $index += $batchSize) {
            $slice = \substr($map, $index, \min($batchSize, $bytesInTotalExceptLast - $index));
            $buffer .= \strtr($slice, $byteMapping);
            if (\strlen($buffer) > self::STREAM_BUFFER_SIZE) {
                self::stream($stream, $buffer);
                $buffer = '';
            }
        }
        if ($this->elementCount > 0) {
            $lastByte = \substr(\strtr(\substr($map, -1), $byteMapping), 0, -1);
            $buffer .= \preg_replace(\sprintf('~(,[^,]+){%d}$~', 8 - ($this->bitCount & 7)), '', $lastByte);
        }
        self::stream($stream, $buffer.']');
    }

    // `AbstractBytemap`
    protected function calculateHowManyToSkip(bool $searchForwards, ?int $startAfter): ?int
    {
        if (null === $startAfter) {
            return 0;
        }

        if ($startAfter < 0) {
            $startAfter += $this->bitCount;
        }

        if ($searchForwards) {
            return $startAfter < $this->bitCount - 1 ? \max(0, $startAfter + 1) : null;
        }

        return $startAfter > 0 ? \max(0, $this->bitCount - $startAfter) : null;
    }

    protected function unserializeAndValidate(string $serialized): void
    {
        $errorMessage = 'details unavailable';
        \set_error_handler(static function (int $errno, string $errstr) use (&$errorMessage): void {
            $errorMessage = $errstr;
        });
        $result = \unserialize($serialized, ['allowed_classes' => static::UNSERIALIZED_CLASSES]);
        \restore_error_handler();

        if (false === $result) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.'Failed to unserialize ('.$errorMessage.')');
        }
        if (!\is_array($result) || !\in_array(\array_keys($result), [[0, 1], [1, 0]], true)) {
            throw new \UnexpectedValueException(self::EXCEPTION_PREFIX.'Failed to unserialize (expected an array of two elements)');
        }

        $this->defaultValue = self::DEFAULT_BYTEMAP_VALUE;
        [$bitCount, $this->map] = $result;

        if (!\is_int($bitCount)) {
            throw new \TypeError(self::EXCEPTION_PREFIX.'Failed to unserialize (the number of bits must be an integer, '.\gettype($this->bitCount).' given)');
        }
        if ($bitCount < 0) {
            throw new \DomainException(self::EXCEPTION_PREFIX.'Failed to unserialize (the number of bits must not be negative)');
        }

        $this->bitCount = $bitCount;
    }
}

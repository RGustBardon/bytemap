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

}

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
trait InvalidTypeGeneratorsTrait
{
    protected static function generateIndicesOfInvalidType(): \Generator
    {
        yield from [
            false, true,
            0., 1.,
            '', '+0', '00', '01', '0e0', '0a', 'a0', '01', '1e0', '1a', 'a1',
            [], [0], [1],
            new \stdClass(), new class() {
                public function __toString(): string
                {
                    return '0';
                }
            },
            \fopen('php://memory', 'rb'),
            static function (): int { return 0; },
            static function (): \Generator { yield 0; },
        ];
    }

    /**
     * @param mixed $defaultValue
     */
    protected static function generateElementsOfInvalidType($defaultValue): \Generator
    {
        $expectedType = \gettype($defaultValue);
        foreach ([
            false, true,
            0, 1, 10, 42,
            0., 1., 10., 42.,
            [], [0], [1],
            'hello, world!',
            new \stdClass(), new class() {
                public function __toString(): string
                {
                    return '0';
                }
            },
            \fopen('php://memory', 'rb'),
            static function (): int { return 0; },
            static function (): \Generator { yield 0; },
        ] as $value) {
            if (\gettype($value) !== $expectedType) {
                yield $value;
            }
        }
    }
}

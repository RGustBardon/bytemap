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
 * @covers \Bytemap\AbstractBytemap
 * @covers \Bytemap\Benchmark\AbstractDsBytemap
 * @covers \Bytemap\Benchmark\ArrayBytemap
 * @covers \Bytemap\Benchmark\DsDequeBytemap
 * @covers \Bytemap\Benchmark\DsVectorBytemap
 * @covers \Bytemap\Benchmark\SplBytemap
 * @covers \Bytemap\Bytemap
 * @covers \Bytemap\JsonListener\BytemapListener
 */
final class JsonStreamTest extends AbstractTestOfBytemap
{
    use JsonStreamTestTrait;

    // `JsonStreamTestTrait`
    public static function jsonStreamInstanceProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['b', 'd', 'f'],
                ['bd', 'df', 'gg'],
            ] as $elements) {
                $defaultValue = \str_repeat("\x0", \strlen($elements[0]));
                $invalidValue = $defaultValue."\x0";
                yield [new $impl($defaultValue), $defaultValue, $invalidValue, $elements];
            }
        }
    }
}

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

    // `JsonStreamTest`
    public static function validJsonProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $elements]) {
            foreach ([0, \JSON_FORCE_OBJECT] as $jsonEncodingOptions) {
                foreach ([false, true] as $useStreamingParser) {
                    yield [$impl, $elements, $jsonEncodingOptions, $useStreamingParser];
                }
            }
        }
    }

    /**
     * @covers \Bytemap\Benchmark\AbstractDsBytemap::parseJsonStream
     * @covers \Bytemap\Benchmark\ArrayBytemap::parseJsonStream
     * @covers \Bytemap\Benchmark\SplBytemap::parseJsonStream
     * @covers \Bytemap\Bytemap::parseJsonStream
     * @covers \Bytemap\JsonListener\BytemapListener
     * @dataProvider validJsonProvider
     */
    public function testParsing(string $impl, array $elements, int $jsonEncodingOptions, bool $useStreamingParser): void
    {
        $bytemap = self::instantiate($impl, $elements[1]);

        self::assertStreamParsing([], [], $jsonEncodingOptions, $bytemap, $elements[0], $useStreamingParser);

        $sequence = [$elements[1], $elements[2], $elements[1], $elements[0], $elements[0]];
        self::assertStreamParsing($sequence, $sequence, $jsonEncodingOptions, $bytemap, $elements[0], $useStreamingParser);

        $expectedSequence = $sequence;
        $expectedSequence[3] = $elements[0];
        unset($sequence[3]);
        self::assertStreamParsing($expectedSequence, $sequence, $jsonEncodingOptions, $bytemap, $elements[0], $useStreamingParser);

        $sequence = \array_reverse($sequence, true);
        self::assertStreamParsing($expectedSequence, $sequence, $jsonEncodingOptions, $bytemap, $elements[0], $useStreamingParser);

        $expectedSequence = [$elements[0], $elements[1], $elements[2]];
        $sequence = [1 => $elements[1], 0 => $elements[0], 2 => $elements[2]];
        self::assertStreamParsing($expectedSequence, $sequence, $jsonEncodingOptions, $bytemap, $elements[0], $useStreamingParser);

        $sequence = [1 => $elements[1], 2 => $elements[2]];
        self::assertStreamParsing($expectedSequence, $sequence, $jsonEncodingOptions, $bytemap, $elements[0], $useStreamingParser);
    }
}

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
                yield [new $impl($defaultValue), $defaultValue, $elements];
            }
        }
    }

    // `JsonStreamTest`

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider implementationProvider
     */
    public function testParsingClosedResource(string $impl): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('open resource');

        self::instantiate($impl, "\x0")::parseJsonStream(self::getClosedStream(), "\x0");
    }

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider implementationProvider
     */
    public function testParsingNonStream(string $impl): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('process');

        $bytemap = self::instantiate($impl, "\x0");
        $process = self::getProcess();

        try {
            $bytemap::parseJsonStream($process, "\x0");
        } finally {
            \proc_close($process);
        }
    }

    public static function invalidJsonDataProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([false, true] as $useStreamingParser) {
                foreach ([
                    ['}', \UnexpectedValueException::class, 'failed to parse JSON'],
                    ['"a"', \UnexpectedValueException::class, 'expected an array or an object|failed to parse JSON'],
                    ['{0:"a"}', \UnexpectedValueException::class, 'failed to parse JSON'],
                    ['[2]', \TypeError::class, 'must be of (?:the )?type string'],
                    ['["ab"]', \DomainException::class, 'value must be exactly'],
                    ['["a", "ab"]', \DomainException::class, 'value must be exactly'],
                    ['{"a": "a"}', \TypeError::class, 'must be of (?:the )?type int'],
                    ['{"-1":"a"}', \OutOfRangeException::class, 'negative index'],
                    ['{"0":"ab"}', \DomainException::class, 'value must be exactly'],
                    ['{"0":"a","1":"ab"}', \DomainException::class, 'value must be exactly'],
                ] as [$invalidJsonData, $expectedThrowable, $pattern]) {
                    yield [$impl, $useStreamingParser, $invalidJsonData, $expectedThrowable, $pattern];
                }
            }

            yield from [
                [$impl, false, '[[]]', \TypeError::class, 'must be of type string'],
                [$impl, true, '[[]]', \UnexpectedValueException::class, 'failed to parse JSON'],
            ];
        }
    }

    /**
     * @covers \Bytemap\Benchmark\AbstractDsBytemap::parseJsonStream
     * @covers \Bytemap\Benchmark\ArrayBytemap::parseJsonStream
     * @covers \Bytemap\Benchmark\SplBytemap::parseJsonStream
     * @covers \Bytemap\Bytemap::parseJsonStream
     * @covers \Bytemap\JsonListener\BytemapListener
     * @dataProvider invalidJsonDataProvider
     */
    public function testParsingInvalidData(
        string $impl,
        bool $useStreamingParser,
        string $invalidJsonData,
        string $expectedThrowable,
        string $pattern
    ): void {
        $this->expectException($expectedThrowable);
        $this->expectExceptionMessageRegExp('~'.$pattern.'~i');

        $bytemap = self::instantiate($impl, "\x0");
        $jsonStream = self::getStream($invalidJsonData);
        $_ENV['BYTEMAP_STREAMING_PARSER'] = ($useStreamingParser ? '1' : '0');
        $bytemap::parseJsonStream($jsonStream, "\x0");
    }

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

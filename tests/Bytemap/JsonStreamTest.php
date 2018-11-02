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
 * @covers \Bytemap\Benchmark\ArrayBytemap
 * @covers \Bytemap\Benchmark\DsBytemap
 * @covers \Bytemap\Benchmark\SplBytemap
 * @covers \Bytemap\Bytemap
 * @covers \Bytemap\JsonListener\BytemapListener
 */
final class JsonStreamTest extends AbstractTestOfBytemap
{
    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider implementationProvider
     * @expectedException \TypeError
     * @expectedExceptionMessage open resource
     */
    public function testParsingClosedResource(string $impl): void
    {
        self::instantiate($impl, "\x0")::parseJsonStream(self::getClosedStream(), "\x0");
    }

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider implementationProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage process
     */
    public function testParsingNonStream(string $impl): void
    {
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
                    ['{"a":"ab"}', \TypeError::class, 'must be of (?:the )?type int'],
                    ['{"-1":"ab"}', \OutOfRangeException::class, 'negative index'],
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
     * @covers \Bytemap\Benchmark\ArrayBytemap::parseJsonStream
     * @covers \Bytemap\Benchmark\DsBytemap::parseJsonStream
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
        $bytemap = self::instantiate($impl, "\x0");
        $jsonStream = self::getStream($invalidJsonData);
        if ($useStreamingParser) {
            unset($_ENV['BYTEMAP_STREAMING_PARSER']);
        } else {
            $_ENV['BYTEMAP_STREAMING_PARSER'] = '0';
        }

        try {
            $bytemap::parseJsonStream($jsonStream, "\x0");
        } catch (\Throwable $e) {
            if (!($e instanceof $expectedThrowable)) {
                $format = 'Failed asserting that a throwable of type %s is thrown as opposed to %s with message "%s"';
                self::fail(\sprintf($format, $expectedThrowable, \get_class($e), $e->getMessage()));
            }
        }
        self::assertTrue(isset($e), 'Nothing thrown although "\\'.$expectedThrowable.'" was expected.');
        self::assertRegExp('~'.$pattern.'~i', $e->getMessage());
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
     * @covers \Bytemap\Benchmark\ArrayBytemap::parseJsonStream
     * @covers \Bytemap\Benchmark\DsBytemap::parseJsonStream
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

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider implementationProvider
     * @expectedException \TypeError
     */
    public function testStreamingToClosedResource(string $impl): void
    {
        self::instantiate($impl, "\x0")->streamJson(self::getClosedStream());
    }

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider implementationProvider
     * @expectedException \InvalidArgumentException
     */
    public function testStreamingToNonStream(string $impl): void
    {
        $bytemap = self::instantiate($impl, "\x0");
        $process = self::getProcess();

        try {
            $bytemap->streamJson($process);
        } finally {
            \proc_close($process);
        }
    }

    /**
     * @covers \Bytemap\Benchmark\ArrayBytemap::streamJson
     * @covers \Bytemap\Benchmark\DsBytemap::streamJson
     * @covers \Bytemap\Benchmark\SplBytemap::streamJson
     * @covers \Bytemap\Bytemap::streamJson
     * @dataProvider arrayAccessProvider
     */
    public function testStreaming(string $impl, array $elements): void
    {
        $bytemap = self::instantiate($impl, $elements[0]);

        self::assertStreamWriting([], $bytemap);

        $sequence = [$elements[1], $elements[2], $elements[1]];
        self::pushElements($bytemap, ...$sequence);
        $bytemap[4] = $elements[0];
        \array_push($sequence, $elements[0], $elements[0]);

        self::assertStreamWriting($sequence, $bytemap);

        // The size of streamed JSON data should exceed `AbstractBytemap::STREAM_BUFFER_SIZE`.
        $bytemap = self::instantiateWithSize($impl, $elements, 32 * 1024);
        self::assertStreamWriting(\iterator_to_array($bytemap->getIterator()), $bytemap);
    }

    private static function assertStreamParsing(
        array $expectedSequence,
        array $sequence,
        int $jsonEncodingOptions,
        BytemapInterface $instance,
        $defaultElement,
        bool $useStreamingParser
        ): void {
        $json = \json_encode($sequence, $jsonEncodingOptions);
        self::assertNotFalse($json);

        $jsonStream = self::getStream($json);
        if ($useStreamingParser) {
            unset($_ENV['BYTEMAP_STREAMING_PARSER']);
        } else {
            $_ENV['BYTEMAP_STREAMING_PARSER'] = '0';
        }
        $bytemap = $instance::parseJsonStream($jsonStream, $defaultElement);

        self::assertSame('resource', \gettype($jsonStream));
        self::assertNotSame($instance, $bytemap);
        self::assertSequence($expectedSequence, $bytemap);
        self::assertDefaultElement($defaultElement, $bytemap, \str_repeat("\xff", \strlen($defaultElement)));
    }

    private static function getClosedStream()
    {
        $resource = self::getStream('[]');
        \fclose($resource);

        return $resource;
    }

    private static function getProcess()
    {
        if (!\function_exists('proc_open')) {
            self::markTestSkipped('This test requires the proc_open function.');
        }

        $otherOptions = ['suppress_errors' => true, 'bypass_shell' => false];
        $process = \proc_open('', [], $pipes, null, null, $otherOptions);

        if (!\is_resource($process)) {
            self::markTestSkipped('Could not create a process resource.');
        }

        return $process;
    }

    private static function getStream(string $contents)
    {
        $stream = \fopen('php://memory', 'r+b');
        self::assertNotFalse($stream);
        \fwrite($stream, $contents);
        \rewind($stream);

        return $stream;
    }

    private static function assertStreamWriting(array $expected, BytemapInterface $bytemap): void
    {
        $json = \json_encode($expected);
        self::assertNotFalse($json);

        $stream = \fopen('php://memory', 'r+b');
        self::assertNotFalse($stream);
        $bytemap->streamJson($stream);

        \rewind($stream);
        self::assertSame($json, \stream_get_contents($stream));
        \fclose($stream);
    }
}

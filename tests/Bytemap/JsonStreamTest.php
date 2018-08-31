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
     * @expectedExceptionMessage expected an open resource
     */
    public function testParsingClosedResource(string $impl): void
    {
        self::instantiate($impl, "\x00")::parseJsonStream(self::getClosedStream(), "\x00");
    }

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider implementationProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage expected a stream
     */
    public function testParsingNonStream(string $impl): void
    {
        $process = self::getProcess();

        try {
            self::instantiate($impl, "\x00")::parseJsonStream($process, "\x00");
        } finally {
            \proc_close($process);
        }
    }

    public static function invalidJsonProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $items]) {
            foreach (['}', '"a"'] as $invalidJson) {
                foreach ([false, true] as $useStreamingParser) {
                    yield [$impl, $useStreamingParser, $invalidJson];
                }
            }
            yield [$impl, true, '[[]]'];
        }
    }

    /**
     * @covers \Bytemap\JsonListener\BytemapListener
     * @dataProvider invalidJsonProvider
     * @expectedException \UnexpectedValueException
     */
    public function testParsingException(string $impl, bool $useStreamingParser, string $invalidJson): void
    {
        $jsonStream = self::getStream($invalidJson);
        if ($useStreamingParser) {
            unset($_ENV['BYTEMAP_STREAMING_PARSER']);
        } else {
            $_ENV['BYTEMAP_STREAMING_PARSER'] = '0';
        }
        self::instantiate($impl, "\x00")::parseJsonStream($jsonStream, "\x00");
    }

    public static function validJsonProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $items]) {
            foreach ([0, \JSON_FORCE_OBJECT] as $jsonEncodingOptions) {
                foreach ([false, true] as $useStreamingParser) {
                    yield [$impl, $items, $jsonEncodingOptions, $useStreamingParser];
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
    public function testParsing(string $impl, array $items, int $jsonEncodingOptions, bool $useStreamingParser): void
    {
        $instance = self::instantiate($impl, $items[1]);

        self::assertStreamParsing([], [], $jsonEncodingOptions, $instance, $items[0], $useStreamingParser);

        $sequence = [$items[1], $items[2], $items[1], $items[0], $items[0]];
        self::assertStreamParsing($sequence, $sequence, $jsonEncodingOptions, $instance, $items[0], $useStreamingParser);

        $expectedSequence = $sequence;
        $expectedSequence[3] = $items[0];
        unset($sequence[3]);
        self::assertStreamParsing($expectedSequence, $sequence, $jsonEncodingOptions, $instance, $items[0], $useStreamingParser);

        $sequence = \array_reverse($sequence, true);
        self::assertStreamParsing($expectedSequence, $sequence, $jsonEncodingOptions, $instance, $items[0], $useStreamingParser);
    }

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider implementationProvider
     * @expectedException \TypeError
     * @expectedExceptionMessage expected an open resource
     */
    public function testStreamingToClosedResource(string $impl): void
    {
        self::instantiate($impl, "\x00")->streamJson(self::getClosedStream());
    }

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider implementationProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage expected a stream
     */
    public function testStreamingToNonStream(string $impl): void
    {
        $process = self::getProcess();

        try {
            self::instantiate($impl, "\x00")->streamJson($process);
        } finally {
            \proc_close($process);
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::streamJson
     * @dataProvider arrayAccessProvider
     */
    public function testStreaming(string $impl, array $items): void
    {
        $bytemap = self::instantiate($impl, $items[0]);

        self::assertStreamWriting([], $bytemap);

        $sequence = [$items[1], $items[2], $items[1]];
        self::pushItems($bytemap, ...$sequence);
        $bytemap[4] = $items[0];
        \array_push($sequence, $items[0], $items[0]);

        self::assertStreamWriting($sequence, $bytemap);
    }

    private static function assertStreamParsing(
        array $expectedSequence,
        array $sequence,
        int $jsonEncodingOptions,
        BytemapInterface $instance,
        $defaultItem,
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
        $bytemap = $instance::parseJsonStream($jsonStream, $defaultItem);

        self::assertSame('resource', \gettype($jsonStream));
        self::assertNotSame($instance, $bytemap);
        self::assertSequence($expectedSequence, $bytemap);
        self::assertDefaultItem($defaultItem, $bytemap, \str_repeat("\xff", \strlen($defaultItem)));
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
        $stream = \fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        \fwrite($stream, $contents);
        \rewind($stream);

        return $stream;
    }

    private static function assertStreamWriting(array $expected, BytemapInterface $bytemap): void
    {
        $json = \json_encode($expected);
        self::assertNotFalse($json);

        $stream = \fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        $bytemap->streamJson($stream);

        \rewind($stream);
        self::assertSame($json, \stream_get_contents($stream));
        \fclose($stream);
    }
}

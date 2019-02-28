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
trait JsonStreamTestTrait
{
    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider jsonStreamInstanceProvider
     *
     * @param mixed $defaultValue
     */
    public function testStreamingToClosedResource(BytemapInterface $bytemap, $defaultValue, array $elements): void
    {
        $this->expectException(\TypeError::class);
        $bytemap->streamJson(self::getClosedStream());
    }

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider jsonStreamInstanceProvider
     *
     * @param mixed $defaultValue
     */
    public function testStreamingToNonStream(BytemapInterface $bytemap, $defaultValue, array $elements): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $process = self::getProcess();

        try {
            $bytemap->streamJson($process);
        } finally {
            \proc_close($process);
        }
    }

    /**
     * @covers \Bytemap\Benchmark\AbstractDsBytemap::streamJson
     * @covers \Bytemap\Benchmark\ArrayBytemap::streamJson
     * @covers \Bytemap\Benchmark\SplBytemap::streamJson
     * @covers \Bytemap\Bytemap::streamJson
     * @dataProvider jsonStreamInstanceProvider
     *
     * @param mixed $defaultValue
     */
    public function testStreaming(BytemapInterface $bytemap, $defaultValue, array $elements): void
    {
        self::assertStreamWriting([], $bytemap);

        $sequence = [];
        foreach ([1, 2, 1, null, 0] as $sequenceIndex => $elementIndex) {
            $element = null === $elementIndex ? $defaultValue : $elements[$elementIndex];
            $sequence[$sequenceIndex] = $element;
            if (null !== $elementIndex) {
                $bytemap[$sequenceIndex] = $element;
            }
        }
        self::assertStreamWriting($sequence, $bytemap);

        // The size of streamed JSON data should exceed `AbstractBytemap::STREAM_BUFFER_SIZE`.
        for ($i = \count($elements), $sizeOfSeed = \count($elements); $i < 32 * 1024; ++$i) {
            $bytemap[$i] = $elements[$i % $sizeOfSeed];
        }
        self::assertStreamWriting(\iterator_to_array($bytemap->getIterator()), $bytemap);
    }

    abstract public static function assertNotFalse($condition, string $message = ''): void;

    abstract public static function assertSame($expected, $actual, string $message = ''): void;

    abstract public static function jsonStreamInstanceProvider(): \Generator;

    abstract public static function markTestSkipped(string $message = ''): void;

    abstract public function expectException(string $exception): void;

    protected static function getClosedStream()
    {
        $resource = self::getStream('[]');
        \fclose($resource);

        return $resource;
    }

    protected static function getProcess()
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

    protected static function getStream(string $contents)
    {
        $stream = \fopen('php://memory', 'r+b');
        self::assertNotFalse($stream);
        \fwrite($stream, $contents);
        \rewind($stream);

        return $stream;
    }

    protected static function assertStreamWriting(array $expected, BytemapInterface $bytemap): void
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

    protected static function assertStreamParsing(
        array $expectedSequence,
        array $sequence,
        int $jsonEncodingOptions,
        BytemapInterface $instance,
        $defaultValue,
        bool $useStreamingParser
    ): void {
        $json = \json_encode($sequence, $jsonEncodingOptions);
        self::assertNotFalse($json);

        $jsonStream = self::getStream($json);
        $_ENV['BYTEMAP_STREAMING_PARSER'] = ($useStreamingParser ? '1' : '0');
        $bytemap = $instance::parseJsonStream($jsonStream, $defaultValue);

        self::assertSame('resource', \gettype($jsonStream));
        self::assertNotSame($instance, $bytemap);
        self::assertSequence($expectedSequence, $bytemap);
        self::assertDefaultValue($defaultValue, $bytemap, \str_repeat("\xff", \strlen($defaultValue)));
    }

    abstract protected static function assertDefaultValue($defaultValue, BytemapInterface $bytemap, $newElement): void;

    abstract protected static function assertSequence(array $sequence, BytemapInterface $bytemap): void;
}

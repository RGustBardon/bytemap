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
     * @param mixed $outOfRangeValue
     */
    public function testParsingClosedResource(
        BytemapInterface $bytemap,
        $defaultValue,
        $outOfRangeValue,
        array $elements
    ): void {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('open resource');

        $bytemap::parseJsonStream(self::getClosedStream(), $defaultValue);
    }

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider jsonStreamInstanceProvider
     *
     * @param mixed $defaultValue
     * @param mixed $outOfRangeValue
     */
    public function testParsingNonStream(
        BytemapInterface $bytemap,
        $defaultValue,
        $outOfRangeValue,
        array $elements
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('process');

        $process = self::getProcess();

        try {
            $bytemap::parseJsonStream($process, $defaultValue);
        } finally {
            \proc_close($process);
        }
    }

    public static function invalidJsonDataProvider(): \Generator
    {
        foreach (self::jsonStreamInstanceProvider() as [$bytemap, $defaultValue, $outOfRangeValue, $elements]) {
            $wrongTypeValue = \is_string($defaultValue) ? (int) \str_repeat('1', \strlen($defaultValue)) : 'ab';
            $wrongTypeValue = \json_encode($wrongTypeValue);
            foreach ([false, true] as $useStreamingParser) {
                foreach ([
                    ['}', \UnexpectedValueException::class, 'failed to parse JSON'],
                    ['"a"', \UnexpectedValueException::class, 'expected an array or an object|failed to parse JSON'],
                    ['{0:'.$defaultValue.'}', \UnexpectedValueException::class, 'failed to parse JSON'],
                    // ['['.$wrongTypeValue.']', \TypeError::class, 'value must be'],
                    ['{"a":'.\json_encode($defaultValue).'}', \TypeError::class, 'must be of (?:the )?type int'],
                    ['{"-1":'.\json_encode($defaultValue).'}', \OutOfRangeException::class, 'negative index'],
                ] as [$invalidJsonData, $expectedThrowable, $pattern]) {
                    yield [clone $bytemap, $defaultValue, $useStreamingParser, $invalidJsonData, $expectedThrowable, $pattern];
                }

                if (null !== $outOfRangeValue) {
                    $outOfRangeValue = \json_encode($outOfRangeValue);
                    foreach ([
                        ['['.$outOfRangeValue.']', \DomainException::class, 'value must be'],
                        ['['.\json_encode($defaultValue).', '.$outOfRangeValue.']', \DomainException::class, 'value must be'],
                        ['{"0":'.$outOfRangeValue.'}', \DomainException::class, 'value must be exactly'],
                        ['{"0":'.\json_encode($defaultValue).',"1":'.$outOfRangeValue.'}', \DomainException::class, 'value must be exactly'],
                    ] as [$invalidJsonData, $expectedThrowable, $pattern]) {
                        yield [clone $bytemap, $defaultValue, $useStreamingParser, $invalidJsonData, $expectedThrowable, $pattern];
                    }
                }
            }

            yield from [
                [clone $bytemap, $defaultValue, false, '[[]]', \TypeError::class, 'value must be'],
                [clone $bytemap, $defaultValue, true, '[[]]', \UnexpectedValueException::class, 'failed to parse JSON'],
            ];
        }
    }

    /**
     * @covers \Bytemap\Benchmark\AbstractDsBytemap::parseJsonStream
     * @covers \Bytemap\Benchmark\ArrayBytemap::parseJsonStream
     * @covers \Bytemap\Benchmark\SplBytemap::parseJsonStream
     * @covers \Bytemap\Bitmap::parseJsonStream
     * @covers \Bytemap\Bytemap::parseJsonStream
     * @covers \Bytemap\JsonListener\BytemapListener
     * @dataProvider invalidJsonDataProvider
     *
     * @param mixed $defaultValue
     */
    public function testParsingInvalidData(
        BytemapInterface $bytemap,
        $defaultValue,
        bool $useStreamingParser,
        string $invalidJsonData,
        string $expectedThrowable,
        string $pattern
    ): void {
        $this->expectException($expectedThrowable);
        $this->expectExceptionMessageRegExp('~'.$pattern.'~i');

        $jsonStream = self::getStream($invalidJsonData);
        $_ENV['BYTEMAP_STREAMING_PARSER'] = ($useStreamingParser ? '1' : '0');
        $bytemap::parseJsonStream($jsonStream, $defaultValue);
    }

    public static function jsonStreamInstanceWithOptionsProvider(): \Generator
    {
        foreach ([0, \JSON_FORCE_OBJECT] as $jsonEncodingOptions) {
            foreach ([false, true] as $useStreamingParser) {
                foreach (self::jsonStreamInstanceProvider() as [$bytemap, $defaultValue, , $elements]) {
                    yield [$bytemap, $defaultValue, $elements, $jsonEncodingOptions, $useStreamingParser];
                }
            }
        }
    }

    /**
     * @covers \Bytemap\Benchmark\AbstractDsBytemap::parseJsonStream
     * @covers \Bytemap\Benchmark\ArrayBytemap::parseJsonStream
     * @covers \Bytemap\Benchmark\SplBytemap::parseJsonStream
     * @covers \Bytemap\Bitmap::parseJsonStream
     * @covers \Bytemap\Bytemap::parseJsonStream
     * @covers \Bytemap\JsonListener\BytemapListener
     * @dataProvider jsonStreamInstanceWithOptionsProvider
     *
     * @param mixed $defaultValue
     */
    public function testParsing(
        BytemapInterface $bytemap,
        $defaultValue,
        array $elements,
        int $jsonEncodingOptions,
        bool $useStreamingParser
    ): void {
        self::assertStreamParsing([], [], $jsonEncodingOptions, $bytemap, $defaultValue, $useStreamingParser);

        $sequence = [$elements[1], $elements[2], $elements[1], $elements[0], $elements[0]];
        self::assertStreamParsing($sequence, $sequence, $jsonEncodingOptions, $bytemap, $defaultValue, $useStreamingParser);

        $expectedSequence = $sequence;
        $expectedSequence[3] = $defaultValue;
        unset($sequence[3]);
        self::assertStreamParsing($expectedSequence, $sequence, $jsonEncodingOptions, $bytemap, $defaultValue, $useStreamingParser);

        $sequence = \array_reverse($sequence, true);
        self::assertStreamParsing($expectedSequence, $sequence, $jsonEncodingOptions, $bytemap, $defaultValue, $useStreamingParser);

        $expectedSequence = [$defaultValue, $elements[1], $elements[2]];
        $sequence = [1 => $elements[1], 0 => $defaultValue, 2 => $elements[2]];
        self::assertStreamParsing($expectedSequence, $sequence, $jsonEncodingOptions, $bytemap, $defaultValue, $useStreamingParser);

        $sequence = [1 => $elements[1], 2 => $elements[2]];
        self::assertStreamParsing($expectedSequence, $sequence, $jsonEncodingOptions, $bytemap, $defaultValue, $useStreamingParser);
    }

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider jsonStreamInstanceProvider
     *
     * @param mixed $defaultValue
     * @param mixed $outOfRangeValue
     */
    public function testStreamingToClosedResource(
        BytemapInterface $bytemap,
        $defaultValue,
        $outOfRangeValue,
        array $elements
    ): void {
        $this->expectException(\TypeError::class);
        $bytemap->streamJson(self::getClosedStream());
    }

    /**
     * @covers \Bytemap\AbstractBytemap::ensureStream
     * @dataProvider jsonStreamInstanceProvider
     *
     * @param mixed $defaultValue
     * @param mixed $outOfRangeValue
     */
    public function testStreamingToNonStream(
        BytemapInterface $bytemap,
        $defaultValue,
        $outOfRangeValue,
        array $elements
    ): void {
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
     * @param mixed $outOfRangeValue
     */
    public function testStreaming(
        BytemapInterface $bytemap,
        $defaultValue,
        $outOfRangeValue,
        array $elements
    ): void {
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
        self::assertDefaultValue($defaultValue, $bytemap, $defaultValue);
    }

    abstract protected static function assertDefaultValue($defaultValue, BytemapInterface $bytemap, $newElement): void;

    abstract protected static function assertSequence(array $sequence, BytemapInterface $bytemap): void;
}

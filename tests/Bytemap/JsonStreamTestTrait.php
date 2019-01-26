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
    abstract public static function assertNotFalse($condition, string $message = ''): void;

    abstract public static function assertSame($expected, $actual, string $message = ''): void;

    abstract public static function markTestSkipped(string $message = ''): void;

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
        if ($useStreamingParser) {
            unset($_ENV['BYTEMAP_STREAMING_PARSER']);
        } else {
            $_ENV['BYTEMAP_STREAMING_PARSER'] = '0';
        }
        $bytemap = $instance::parseJsonStream($jsonStream, $defaultValue);

        self::assertSame('resource', \gettype($jsonStream));
        self::assertNotSame($instance, $bytemap);
        self::assertSequence($expectedSequence, $bytemap);
        self::assertDefaultValue($defaultValue, $bytemap, \str_repeat("\xff", \strlen($defaultValue)));
    }

    abstract protected static function assertDefaultValue($defaultValue, BytemapInterface $bytemap, $newElement): void;

    abstract protected static function assertSequence(array $sequence, BytemapInterface $bytemap): void;
}

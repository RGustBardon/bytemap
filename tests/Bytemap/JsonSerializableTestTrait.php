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
trait JsonSerializableTestTrait
{
    /**
     * @dataProvider jsonSerializableInstanceProvider
     *
     * @param mixed $defaultValue
     */
    public function testJsonSerializable(\JsonSerializable $jsonSerializable, $defaultValue, array $elements): void
    {
        self::assertNativeJson([], $jsonSerializable);

        $sequence = [$elements[1], $elements[2], $elements[1]];
        foreach ($sequence as $element) {
            $jsonSerializable[] = $element;
        }
        $jsonSerializable[4] = $defaultValue;
        \array_push($sequence, $defaultValue, $defaultValue);
        self::assertNativeJson($sequence, $jsonSerializable);
    }

    abstract public static function assertSame($expected, $actual, string $message = ''): void;

    abstract public static function jsonSerializableInstanceProvider(): \Generator;

    private static function assertNativeJson($expected, $actual): void
    {
        $expectedJson = \json_encode($expected);
        self::assertSame(\JSON_ERROR_NONE, \json_last_error());

        $actualJson = \json_encode($actual);
        self::assertSame(\JSON_ERROR_NONE, \json_last_error());

        self::assertSame($expectedJson, $actualJson);
    }
}

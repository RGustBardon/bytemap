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
trait SerializableTestTrait
{
    /**
     * @dataProvider invalidSerializedDataProvider
     */
    public function testUnserializeInvalidData(string $data, string $expectedThrowable, string $expectedMessage): void
    {
        try {
            \unserialize($data);
        } catch (\Throwable $e) {
            if (!($e instanceof $expectedThrowable)) {
                $format = 'Failed asserting that a throwable of type %s is thrown as opposed to %s with message "%s"';
                self::fail(\sprintf($format, $expectedThrowable, \get_class($e), $e->getMessage()));
            }
        }
        self::assertTrue(isset($e), 'Nothing thrown although "\\'.$expectedThrowable.'" was expected.');
        self::assertContains($expectedMessage, $e->getMessage(), '', true);
    }

    /**
     * @param mixed $serializable
     * @param mixed $defaultValue
     *
     * @dataProvider serializableInstanceProvider
     */
    public function testSerializable($serializable, $defaultValue, array $elements): void
    {
        $sequence = [$elements[0], $elements[2], $elements[0]];
        foreach ($sequence as $element) {
            $serializable[] = $element;
        }
        $unserialized = \unserialize(\serialize($serializable), ['allowed_classes' => [\get_class($serializable)]]);
        $elementCount = 0;
        foreach ($unserialized as $index => $element) {
            ++$elementCount;
            self::assertSame($sequence[$index], $element);
        }
        self::assertSame(\count($sequence), $elementCount);
        $unserialized[4] = $elements[1];
        self::assertSame($defaultValue, $unserialized[3]);
        self::assertNotSame($serializable, $unserialized);
    }

    abstract public static function invalidSerializedDataProvider(): \Generator;

    abstract public static function serializableInstanceProvider(): \Generator;
}

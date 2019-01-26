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
trait ArrayAccessTestTrait
{
    public static function nullIndexProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultValue, $elements]) {
            yield [$arrayAccessObject, $elements, null];
        }
    }

    public static function invalidIndexTypeProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultValue, $elements]) {
            foreach (self::generateIndicesOfInvalidType() as $index) {
                yield [$arrayAccessObject, $elements, $index];
            }
        }
    }

    public static function negativeIndexProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultValue, $elements]) {
            yield [$arrayAccessObject, $elements, -1];
        }
    }

    public static function outOfRangeIndexProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultValue, $elements]) {
            yield [$arrayAccessObject, $elements, \count($elements)];
        }
    }

    public static function invalidElementTypeProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultValue, $elements]) {
            foreach (self::generateElementsOfInvalidType($defaultValue) as $invalidElement) {
                yield [$arrayAccessObject, $elements, $invalidElement];
            }
        }
    }

    /**
     * @dataProvider nullIndexProvider
     * @dataProvider invalidIndexTypeProvider
     *
     * @param mixed $index
     */
    public function testExistsInvalidType(\ArrayAccess $arrayAccessObject, array $elements, $index): void
    {
        self::assertFalse(isset($arrayAccessObject[$index]));
    }

    /**
     * @dataProvider negativeIndexProvider
     * @dataProvider outOfRangeIndexProvider
     */
    public function testExistsOutOfRange(\ArrayAccess $arrayAccessObject, array $elements, int $index): void
    {
        self::assertFalse(isset($arrayAccessObject[$index]));
    }

    /**
     * @dataProvider nullIndexProvider
     * @dataProvider invalidIndexTypeProvider
     * @expectedException \TypeError
     *
     * @param mixed $index
     */
    public function testGetInvalidType(\ArrayAccess $arrayAccessObject, array $elements, $index): void
    {
        $arrayAccessObject[$index];
    }

    /**
     * @dataProvider negativeIndexProvider
     * @dataProvider outOfRangeIndexProvider
     * @expectedException \OutOfRangeException
     */
    public function testGetOutOfRange(\ArrayAccess $arrayAccessObject, array $elements, int $index): void
    {
        $arrayAccessObject[$index];
    }

    /**
     * @dataProvider invalidIndexTypeProvider
     * @expectedException \TypeError
     *
     * @param mixed $index
     */
    public function testSetInvalidIndexType(\ArrayAccess $arrayAccessObject, array $elements, $index): void
    {
        $arrayAccessObject[$index] = $elements[0];
    }

    /**
     * @dataProvider negativeIndexProvider
     * @expectedException \OutOfRangeException
     */
    public function testSetNegativeIndex(\ArrayAccess $arrayAccessObject, array $elements, int $index): void
    {
        $arrayAccessObject[$index] = $elements[0];
    }

    /**
     * @dataProvider invalidElementTypeProvider
     * @expectedException \TypeError
     *
     * @param mixed $invalidElement
     */
    public function testSetInvalidElementType(\ArrayAccess $arrayAccessObject, array $elements, $invalidElement): void
    {
        $arrayAccessObject[] = $invalidElement;
    }

    /**
     * @dataProvider nullIndexProvider
     * @dataProvider invalidIndexTypeProvider
     * @doesNotPerformAssertions
     *
     * @param mixed $index
     */
    public function testUnsetInvalidType(\ArrayAccess $arrayAccessObject, array $elements, $index): void
    {
        unset($arrayAccessObject[$index]);
    }

    /**
     * @dataProvider negativeIndexProvider
     * @dataProvider outOfRangeIndexProvider
     * @doesNotPerformAssertions
     */
    public function testUnsetOutOfRange(\ArrayAccess $arrayAccessObject, array $elements, int $index): void
    {
        unset($arrayAccessObject[$index]);
    }

    /**
     * @dataProvider arrayAccessInstanceProvider
     *
     * @param mixed $defaultValue
     */
    public function testArrayAccess(\ArrayAccess $arrayAccessObject, $defaultValue, array $elements): void
    {
        $originalElementCount = \count($elements);

        $stack = $elements;
        while (true) {
            $presentElementCount = \count($stack);
            for ($i = 0; $i < $originalElementCount; ++$i) {
                self::assertSame($i < $presentElementCount, isset($arrayAccessObject[$i]));
            }
            if (!$stack) {
                break;
            }
            foreach ($stack as $index => $element) {
                self::assertSame($element, $arrayAccessObject[$index]);
            }
            unset($arrayAccessObject[$presentElementCount - 1]);
            \array_pop($stack);
        }

        foreach ($elements as $index => $element) {
            $stack[] = $element;
            $arrayAccessObject[] = $element;
            for ($i = 0; $i < $originalElementCount; ++$i) {
                self::assertSame($i <= $index, isset($arrayAccessObject[$i]));
            }
            self::assertFalse(isset($arrayAccessObject[$originalElementCount]));
            foreach ($stack as $stackIndex => $stackElement) {
                self::assertSame($stackElement, $arrayAccessObject[$stackIndex]);
            }
        }

        $queue = $stack;
        while (true) {
            $presentElementCount = \count($queue);
            for ($i = 0; $i < $originalElementCount; ++$i) {
                self::assertSame($i < $presentElementCount, isset($arrayAccessObject[$i]));
            }
            if (!$queue) {
                break;
            }
            foreach ($queue as $index => $element) {
                self::assertSame($element, $arrayAccessObject[$index]);
            }
            unset($arrayAccessObject[0]);
            \array_shift($queue);
        }

        foreach (\array_reverse($elements, true) as $index => $element) {
            $arrayAccessObject[$index] = $element;
            for ($i = 0; $i < $originalElementCount; ++$i) {
                self::assertTrue(isset($arrayAccessObject[$i]));
            }
            self::assertFalse(isset($arrayAccessObject[$originalElementCount]));
            foreach ($elements as $originalIndex => $originalElement) {
                self::assertSame($index > $originalIndex ? $defaultValue : $originalElement, $arrayAccessObject[$originalIndex]);
            }
        }

        $arrayAccessObject[0] = $defaultValue;
        self::assertFalse(isset($arrayAccessObject[$originalElementCount]));
        self::assertSame($defaultValue, $arrayAccessObject[0]);

        $arrayAccessObject[] = $defaultValue;
        $arrayAccessObject[] = $defaultValue;
        unset($arrayAccessObject[$originalElementCount]);
        self::assertSame($defaultValue, $arrayAccessObject[$originalElementCount]);
    }

    /**
     * @dataProvider arrayAccessInstanceProvider
     * @depends testArrayAccess
     * @expectedException \OutOfRangeException
     *
     * @param mixed $defaultValue
     */
    public function testGetFromEmpty(\ArrayAccess $arrayAccessObject, $defaultValue, array $elements): void
    {
        for ($i = \count($elements) - 1; $i >= 0; --$i) {
            unset($arrayAccessObject[$i]);
        }
        $arrayAccessObject[0];
    }

    abstract public static function arrayAccessInstanceProvider(): \Generator;

    abstract public static function assertFalse($condition, string $message = ''): void;

    abstract public static function assertSame($expected, $actual, string $message = ''): void;

    abstract public static function assertTrue($condition, string $message = ''): void;

    abstract protected static function generateIndicesOfInvalidType(): \Generator;

    abstract protected static function generateElementsOfInvalidType($defaultValue): \Generator;
}

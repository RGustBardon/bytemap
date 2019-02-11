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
     *
     * @param mixed $index
     */
    public function testGetInvalidType(\ArrayAccess $arrayAccessObject, array $elements, $index): void
    {
        $this->expectException(\TypeError::class);
        $arrayAccessObject[$index];
    }

    /**
     * @dataProvider negativeIndexProvider
     * @dataProvider outOfRangeIndexProvider
     */
    public function testGetOutOfRange(\ArrayAccess $arrayAccessObject, array $elements, int $index): void
    {
        $this->expectException(\OutOfRangeException::class);
        $arrayAccessObject[$index];
    }

    /**
     * @dataProvider invalidIndexTypeProvider
     *
     * @param mixed $index
     */
    public function testSetInvalidIndexType(\ArrayAccess $arrayAccessObject, array $elements, $index): void
    {
        $this->expectException(\TypeError::class);
        $arrayAccessObject[$index] = $elements[0];
    }

    /**
     * @dataProvider negativeIndexProvider
     */
    public function testSetNegativeIndex(\ArrayAccess $arrayAccessObject, array $elements, int $index): void
    {
        $this->expectException(\OutOfRangeException::class);
        $arrayAccessObject[$index] = $elements[0];
    }

    /**
     * @dataProvider invalidElementTypeProvider
     *
     * @param mixed $invalidElement
     */
    public function testSetInvalidElementType(\ArrayAccess $arrayAccessObject, array $elements, $invalidElement): void
    {
        $this->expectException(\TypeError::class);
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
        do {
            $presentElementCount = \count($stack);
            for ($i = 0; $i < $originalElementCount; ++$i) {
                self::assertSame($i < $presentElementCount, isset($arrayAccessObject[$i]));
            }
            foreach ($stack as $index => $element) {
                self::assertSame($element, $arrayAccessObject[$index]);
            }
            unset($arrayAccessObject[$presentElementCount - 1]);
        } while (null !== \array_pop($stack));

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
        do {
            $presentElementCount = \count($queue);
            for ($i = 0; $i < $originalElementCount; ++$i) {
                self::assertSame($i < $presentElementCount, isset($arrayAccessObject[$i]));
            }
            foreach ($queue as $index => $element) {
                self::assertSame($element, $arrayAccessObject[$index]);
            }
            unset($arrayAccessObject[0]);
        } while (null !== \array_shift($queue));

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
     *
     * @param mixed $defaultValue
     */
    public function testGetFromEmpty(\ArrayAccess $arrayAccessObject, $defaultValue, array $elements): void
    {
        $this->expectException(\OutOfRangeException::class);
        for ($i = \count($elements) - 1; $i >= 0; --$i) {
            unset($arrayAccessObject[$i]);
        }
        $arrayAccessObject[0];
    }

    abstract public static function arrayAccessInstanceProvider(): \Generator;

    abstract public static function assertFalse($condition, string $message = ''): void;

    abstract public static function assertSame($expected, $actual, string $message = ''): void;

    abstract public static function assertTrue($condition, string $message = ''): void;

    abstract public function expectException(string $exception): void;

    abstract protected static function generateIndicesOfInvalidType(): \Generator;

    abstract protected static function generateElementsOfInvalidType($defaultValue): \Generator;
}

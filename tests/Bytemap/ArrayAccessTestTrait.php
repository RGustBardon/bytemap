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
    use InvalidLengthGeneratorTrait;
    use InvalidTypeGeneratorsTrait;

    public static function nullIndexProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultElement, $elements]) {
            yield [$arrayAccessObject, $elements, null];
        }
    }

    public static function invalidIndexTypeProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultElement, $elements]) {
            foreach (self::generateIndicesOfInvalidType() as $index) {
                yield [$arrayAccessObject, $elements, $index];
            }
        }
    }

    public static function negativeIndexProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultElement, $elements]) {
            yield [$arrayAccessObject, $elements, -1];
        }
    }

    public static function outOfRangeIndexProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultElement, $elements]) {
            yield [$arrayAccessObject, $elements, \count($elements)];
        }
    }

    public static function invalidElementTypeProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultElement, $elements]) {
            foreach (self::generateElementsOfInvalidType() as $invalidElement) {
                yield [$arrayAccessObject, $elements, $invalidElement];
            }
        }
    }

    public static function invalidLengthProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultElement, $elements]) {
            foreach (self::generateElementsOfInvalidLength(\strlen($elements[0])) as $invalidElement) {
                yield [$arrayAccessObject, $invalidElement];
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
     * @dataProvider invalidLengthProvider
     * @expectedException \DomainException
     */
    public function testSetInvalidLength(\ArrayAccess $arrayAccessObject, string $invalidElement): void
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
     */
    public function testArrayAccess(\ArrayAccess $arrayAccessObject, string $defaultElement, array $elements): void
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
                self::assertSame($index > $originalIndex ? $defaultElement : $originalElement, $arrayAccessObject[$originalIndex]);
            }
        }

        $arrayAccessObject[0] = $defaultElement;
        self::assertFalse(isset($arrayAccessObject[$originalElementCount]));
        self::assertSame($defaultElement, $arrayAccessObject[0]);
    }

    abstract public static function arrayAccessInstanceProvider(): \Generator;
}

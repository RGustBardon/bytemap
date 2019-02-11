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
trait InvalidLengthTestTrait
{
    public static function invalidLengthProvider(): \Generator
    {
        foreach (self::arrayAccessInstanceProvider() as [$arrayAccessObject, $defaultValue, $elements]) {
            foreach (self::generateElementsOfInvalidLength(\strlen($elements[0])) as $invalidElement) {
                yield [$arrayAccessObject, $invalidElement];
            }
        }
    }

    /**
     * @dataProvider invalidLengthProvider
     */
    public function testSetInvalidLength(\ArrayAccess $arrayAccessObject, string $invalidElement): void
    {
        $this->expectException(\DomainException::class);
        $arrayAccessObject[] = $invalidElement;
    }

    abstract public static function arrayAccessInstanceProvider(): \Generator;

    abstract public function expectException(string $exception): void;

    abstract protected static function generateElementsOfInvalidLength(int $bytesPerElement): \Generator;
}

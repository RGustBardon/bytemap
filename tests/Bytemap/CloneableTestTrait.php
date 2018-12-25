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
trait CloneableTestTrait
{
    /**
     * @param mixed $cloneable
     * @param mixed $defaultValue
     *
     * @dataProvider cloneableInstanceProvider
     */
    public function testCloneable($cloneable, $defaultValue, array $elements): void
    {
        $sequence = [$elements[0], $elements[2], $elements[0]];
        foreach ($sequence as $element) {
            $cloneable[] = $element;
        }
        $clone = clone $cloneable;
        $elementCount = 0;
        foreach ($clone as $index => $element) {
            ++$elementCount;
            self::assertSame($sequence[$index], $element);
        }
        self::assertSame(\count($sequence), $elementCount);
        $clone[4] = $elements[1];
        self::assertSame($defaultValue, $clone[3]);
        self::assertNotSame($sequence, $clone);
        self::assertFalse(isset($cloneable[3]));
    }

    abstract public static function cloneableInstanceProvider(): \Generator;
}

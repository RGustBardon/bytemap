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
trait CountableTestTrait
{
    public static function countableTestProvider(): \Generator
    {
        foreach (self::countableInstanceProvider() as [$countable, $elements]) {
            yield [clone $countable, 0];
            $countable[] = $elements[0];
            yield [clone $countable, 1];
            $countable[] = $elements[0];
            yield [clone $countable, 2];
            $countable[] = $elements[1];
            yield [clone $countable, 3];
            $countable[1] = $elements[1];
            yield [clone $countable, 3];
        }
    }

    /**
     * @dataProvider countableTestProvider
     */
    public function testCountable(\Countable $countable, int $expectedCount): void
    {
        self::assertCount($expectedCount, $countable);
    }

    abstract public static function countableInstanceProvider(): \Generator;
}

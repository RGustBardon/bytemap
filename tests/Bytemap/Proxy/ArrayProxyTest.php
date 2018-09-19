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

namespace Bytemap\Proxy;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 * @covers \Bytemap\Proxy\ArrayProxy
 */
final class ArrayProxyTest extends AbstractTestOfProxy
{
    public function testFill(): void
    {
        $arrayProxy = self::instantiate()::fill('cd', 0, 3);
        self::assertSame(['cd', 'cd', 'cd'], $arrayProxy->exportArray());
    }

    public function testCombine(): void
    {
        $arrayProxy = self::instantiate()::combine('cd', [1, 6, 3], ['ab', 'ef', 'xy']);
        self::assertSame(['cd', 'ab', 'cd', 'xy', 'cd', 'cd', 'ef'], $arrayProxy->exportArray());
    }

    public function testFillKeys(): void
    {
        $arrayProxy = self::instantiate()::fillKeys('cd', [1, 6, 3], 'ab');
        self::assertSame(['cd', 'ab', 'cd', 'ab', 'cd', 'cd', 'ab'], $arrayProxy->exportArray());
    }

    public static function instantiate(): ArrayProxyInterface
    {
        return new ArrayProxy('ab');
    }
}

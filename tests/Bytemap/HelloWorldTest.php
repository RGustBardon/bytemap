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

use PHPUnit\Framework\TestCase;

/**
 * A temporary class to test the test harness.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 * @covers \Bytemap\HelloWorld
 */
final class HelloWorldTest extends TestCase
{
    public function testFoo(): void
    {
        $this->assertSame('hello, world!', (new HelloWorld())->foo());
    }
}

<?php

/**
 * This file is part of the Bytemap package.
 *
 * (c) Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bytemap;

/**
 * A temporary class to test the test harness.
 *
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
final class HelloWorld
{

    public function foo(): string
    {
        return 'hello, world!';
    }

}
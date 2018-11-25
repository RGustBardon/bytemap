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
trait MagicPropertiesTestTrait
{
    /**
     * @dataProvider magicPropertiesInstanceProvider
     * @expectedException \ErrorException
     *
     * @param mixed $instance
     */
    public function testMagicGet($instance): void
    {
        $instance->undefinedProperty;
    }

    /**
     * @dataProvider magicPropertiesInstanceProvider
     * @expectedException \ErrorException
     *
     * @param mixed $instance
     */
    public function testMagicSet($instance): void
    {
        $instance->undefinedProperty = 42;
    }

    /**
     * @dataProvider magicPropertiesInstanceProvider
     * @expectedException \ErrorException
     *
     * @param mixed $instance
     */
    public function testMagicIsset($instance): void
    {
        isset($instance->undefinedProperty);
    }

    /**
     * @dataProvider magicPropertiesInstanceProvider
     * @expectedException \ErrorException
     *
     * @param mixed $instance
     */
    public function testMagicUnset($instance): void
    {
        unset($instance->undefinedProperty);
    }

    abstract public static function magicPropertiesInstanceProvider(): \Generator;
}

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

use Bytemap\JsonListener\BytemapListener;
use JsonStreamingParser\Parser;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
abstract class AbstractBytemap implements BytemapInterface
{
    public function __get($name)
    {
        throw new \ErrorException(\sprintf('Undefined property: %s::$%s', static::class, $name));
    }

    public function __set($name, $value): void
    {
        self::__get($name);
    }

    public function __isset($name): bool
    {
        self::__get($name);
    }

    public function __unset($name): void
    {
        self::__get($name);
    }

    protected static function parseBytemapJsonOnTheFly($jsonStream, $impl): BytemapInterface
    {
        $bytemap = null;
        $listener = new BytemapListener(function ($value, $key) use (&$bytemap, $impl) {
            if (null === $bytemap) {
                $bytemap = new $impl($value);
            } elseif (null === $key) {
                $bytemap[] = $value;
            } else {
                $bytemap[$key] = $value;
            }
        });
        (new Parser($jsonStream, $listener))->parse();

        return $bytemap;
    }
}

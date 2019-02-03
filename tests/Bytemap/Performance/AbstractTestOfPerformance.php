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

namespace Bytemap\Performance;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @BeforeMethods({"setUp"})
 *
 * @internal
 */
abstract class AbstractTestOfPerformance
{
    protected const DEFAULT_INSERTED_PAIRS = [
        [self::DEFAULT_ELEMENT_SHORT, self::INSERTED_ELEMENT_SHORT],
        [self::DEFAULT_ELEMENT_LONG, self::INSERTED_ELEMENT_LONG],
    ];

    private const DEFAULT_ELEMENT_SHORT = "\x0";
    private const DEFAULT_ELEMENT_LONG = "\x0\x0\x0\x0";

    private const INSERTED_ELEMENT_SHORT = "\x1";
    private const INSERTED_ELEMENT_LONG = "\x1\x2\x3\x4";
}

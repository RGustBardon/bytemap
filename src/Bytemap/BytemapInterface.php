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
 */
interface BytemapInterface extends \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, \Serializable
{
    /**
     * @param resource $jsonStream
     * @param bool     $useStreamingParser an internal parameter that should only be used while testing
     *
     * @return self
     */
    public static function parseJsonStream($jsonStream, bool $useStreamingParser = true): self;
}
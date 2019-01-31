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

use Bytemap\Bitmap;
use Bytemap\Bytemap;
use Bytemap\Benchmark\ArrayBytemap;
use Bytemap\Benchmark\DsDequeBytemap;
use Bytemap\Benchmark\DsVectorBytemap;
use Bytemap\Benchmark\SplBytemap;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @BeforeMethods({"setUp"})
 *
 * @internal
 */
final class BytemapPerformance
{
    private const IMPLEMENTATIONS = [
        ArrayBytemap::class => "\x1",
        DsDequeBytemap::class => "\x1",
        DsVectorBytemap::class => "\x1",
        SplBytemap::class => "\x1",
        Bytemap::class => "\x1",
        Bitmap::class => true,
    ];
    
    private $bytemap;
    
    private $element;

    private $lastIndex;
    
    public function provideImplementations(): \Generator
    {
        foreach (self::IMPLEMENTATIONS as $implementation => $element) {
            $reflectionClass = new \ReflectionClass($implementation);
            yield $reflectionClass->getShortName() => [$implementation, $element];
        }
    }

    public function setUp(array $params): void
    {
        [$implementation, $this->element] = $params;
        $this->bytemap = new $implementation("\x0");
        $this->lastIndex = 0;
        \mt_srand(0);
    }

    /**
     * @ParamProviders({"provideImplementations"})
     * @revs(20000)
     */
    public function benchNativeExpand(): void
    {
        $this->lastIndex += \mt_rand(1, 100);
        $this->bytemap[$this->lastIndex] = $this->element;
    }
}

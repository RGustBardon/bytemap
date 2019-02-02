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
 * @AfterMethods({"tearDown"})
 *
 * @internal
 */
final class BytemapPerformance
{
    private const DEFAULT_ELEMENT_SHORT = "\x0";
    private const DEFAULT_ELEMENT_LONG = "\x0\x0\x0\x0";
    
    private const INSERTED_ELEMENT_SHORT = "\x1";
    private const INSERTED_ELEMENT_LONG = "\x1\x2\x3\x4";
    
    private const DEFAULT_INSERTED_PAIRS = [
        [self::DEFAULT_ELEMENT_SHORT, self::INSERTED_ELEMENT_SHORT],
        [self::DEFAULT_ELEMENT_LONG, self::INSERTED_ELEMENT_LONG],
    ];
    
    private const IMPLEMENTATIONS = [
        ArrayBytemap::class => self::DEFAULT_INSERTED_PAIRS,
        DsDequeBytemap::class => self::DEFAULT_INSERTED_PAIRS,
        DsVectorBytemap::class => self::DEFAULT_INSERTED_PAIRS,
        SplBytemap::class => self::DEFAULT_INSERTED_PAIRS,
        Bytemap::class => self::DEFAULT_INSERTED_PAIRS,
    ];
    
    private $bytemap;
    
    private $element;

    private $lastIndex;
    
    private $operationCount;
    
    public function provideImplementations(): \Generator
    {
        foreach (self::IMPLEMENTATIONS as $implementation => $default_inserted_pairs) {
            $shortName = (new \ReflectionClass($implementation))->getShortName();
            foreach ($default_inserted_pairs as [$default, $inserted]) {
                $key = $shortName.'-'.\strlen($default);
                yield $key => [$implementation, $default, $inserted];
            }
        }
    }

    public function setUp(array $params): void
    {
        [$implementation, $defaultElement, $this->element] = $params;
        $this->bytemap = new $implementation($defaultElement);
        $this->lastIndex = 0;
        $this->operationCount = 0;
        \mt_srand(0);
    }
   
    public function tearDown(array $params): void
    {
        [, , $insertedElement] = $params;
        $operationCount = 0;
        foreach ($this->bytemap->find([$insertedElement]) as $element) {
            ++$operationCount;
        }
        \assert($this->operationCount === $operationCount);
    }

    /**
     * @ParamProviders({"provideImplementations"})
     * @Revs(20000)
     */
    public function benchNativeExpand(): void
    {
        $this->lastIndex += \mt_rand(1, 100);
        $this->bytemap[$this->lastIndex] = $this->element;
        ++$this->operationCount;
    }
}

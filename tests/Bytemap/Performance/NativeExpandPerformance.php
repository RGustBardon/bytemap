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
 * @AfterMethods({"tearDown"})
 *
 * @internal
 */
final class NativeExpandPerformance extends AbstractTestOfPerformance
{
    private const INTERVALS = [1, 10, 100];

    private /* string */ $element;

    private /* int */ $lastIndex;

    private /* int */ $operationCount;

    public function setUp(array $params): void
    {
        parent::setUp($params);
        
        [, , , $this->element, ] = $params;
        $this->lastIndex = 0;
        $this->operationCount = 0;
    }

    public function tearDown(array $params): void
    {
        [, , , $insertedElement, ] = $params;
        $operationCount = 0;
        foreach ($this->bytemap->find([$insertedElement]) as $element) {
            ++$operationCount;
        }
        \assert($this->operationCount === $operationCount);
    }

    public function provideImplementationsAndIntervals(): \Generator
    {
        foreach (self::provideImplementations() as $testId => [$testId, $implementation, $default, $inserted]) {
            foreach (self::INTERVALS as $interval) {
                $testIdWithInterval = $testId.'-'.$interval;
                yield $testIdWithInterval => [$testIdWithInterval, $implementation, $default, $inserted, $interval];
            }
        }
    }

    /**
     * @Groups({"Memory"})
     * @ParamProviders({"provideImplementationsAndIntervals"})
     * @Revs(20000)
     */
    public function benchNativeExpand(array $params): void
    {
        [, , , , $interval] = $params;
        $this->lastIndex += \mt_rand(1, $interval);
        $this->bytemap[$this->lastIndex] = $this->element;
        ++$this->operationCount;
    }
}

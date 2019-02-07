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

use Bytemap\Bytemap;
use Bytemap\BytemapInterface;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
final class ExpansionPerformance extends AbstractTestOfPerformance
{
    private const INTERVALS = [1, 10, 100];

    private /* int */ $lastIndex;

    private /* array */ $array;

    private /* BytemapInterface */ $bytemap;

    private /* \Ds\Sequence */ $dsDeque;

    private /* \Ds\Vector */ $dsVector;

    private /* \SplFixedArray */ $splFixedArray;

    public function setUp(array $params): void
    {
        [$default, , ] = $params;

        $this->lastIndex = 0;

        $this->array = [];
        $this->bytemap = new Bytemap($default);
        $this->dsDeque = new \Ds\Deque([$default]);
        $this->dsVector = new \Ds\Vector([$default]);
        $this->splFixedArray = new \SplFixedArray();
    }

    public function providePairsAndIntervals(): \Generator
    {
        foreach (self::DEFAULT_INSERTED_PAIRS as [$default, $inserted]) {
            foreach (self::INTERVALS as $interval) {
                yield \sprintf('elementLength:%d maximumGap:%d', \strlen($default), $interval) => [$default, $inserted, $interval];
            }
        }
    }

    /**
     * @Groups({"Memory"})
     * @ParamProviders({"providePairsAndIntervals"})
     * @Revs(100000)
     */
    public function benchExpansionBaseline(array $params): void
    {
    }

    /**
     * @Groups({"Memory"})
     * @ParamProviders({"providePairsAndIntervals"})
     * @Revs(100000)
     */
    public function benchExpansionWithArray(array $params): void
    {
        [, $inserted, $interval] = $params;
        $this->array[$this->lastIndex += $interval] = $inserted;
    }

    /**
     * @Groups({"Memory"})
     * @ParamProviders({"providePairsAndIntervals"})
     * @Revs(100000)
     */
    public function benchExpansionWithBytemap(array $params): void
    {
        [, $inserted, $interval] = $params;
        $this->bytemap[$this->lastIndex += $interval] = $inserted;
    }

    /**
     * @Groups({"Memory"})
     * @ParamProviders({"providePairsAndIntervals"})
     * @Revs(100000)
     */
    public function benchExpansionWithDsDeque(array $params): void
    {
        [$default, $inserted, $interval] = $params;
        if ($interval > 1) {
            $count = $this->lastIndex;
            $this->lastIndex += $interval;
            $this->dsDeque->allocate($this->lastIndex);
            $elements = \array_fill(0, (int) $this->lastIndex - $count - 1, $default);
            $elements[] = $inserted;
            $this->dsDeque->push(...$elements);
        } else {
            $this->dsDeque->push($inserted);
        }
    }

    /**
     * @Groups({"Memory"})
     * @ParamProviders({"providePairsAndIntervals"})
     * @Revs(100000)
     */
    public function benchExpansionWithDsVector(array $params): void
    {
        [$default, $inserted, $interval] = $params;
        if ($interval > 1) {
            $count = $this->lastIndex;
            $this->lastIndex += $interval;
            $this->dsVector->allocate($this->lastIndex);
            $elements = \array_fill(0, (int) $this->lastIndex - $count - 1, $default);
            $elements[] = $inserted;
            $this->dsVector->push(...$elements);
        } else {
            $this->dsVector->push($inserted);
        }
    }

    /**
     * @Groups({"Memory"})
     * @ParamProviders({"providePairsAndIntervals"})
     * @Revs(100000)
     */
    public function benchExpansionWithSplFixedArray(array $params): void
    {
        [, $inserted, $interval] = $params;
        $this->lastIndex += $interval;
        $this->splFixedArray->setSize($this->lastIndex + 1);
        $this->splFixedArray[$this->lastIndex] = $inserted;
    }
}

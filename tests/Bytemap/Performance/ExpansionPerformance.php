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
 * @Groups({"Memory"})
 * @ParamProviders({"providePairsAndIntervals"})
 * @Revs(100000)
 *
 * @internal
 */
final class ExpansionPerformance extends AbstractTestOfPerformance
{
    private /* int */ $lastIndex = 0;

    public function setUp(array $params): void
    {
        parent::setUp($params);

        [$default, , ] = $params;

        $this->dsDeque->push($default);
        $this->dsVector->push($default);
    }

    public function benchExpansionBaseline(array $params): void
    {
    }

    public function benchExpansionWithArray(array $params): void
    {
        [, $inserted, $interval] = $params;
        $this->array[$this->lastIndex += $interval] = $inserted;
    }

    public function benchExpansionWithBytemap(array $params): void
    {
        [, $inserted, $interval] = $params;
        $this->bytemap[$this->lastIndex += $interval] = $inserted;
    }

    public function benchExpansionWithDsDeque(array $params): void
    {
        [$default, $inserted, $interval] = $params;
        if ($interval > 1) {
            $count = $this->lastIndex;
            $this->lastIndex += $interval;
            $this->dsDeque->allocate($this->lastIndex);
            $elements = \array_fill(0, (int) ($this->lastIndex - $count - 1), $default);
            $elements[] = $inserted;
            $this->dsDeque->push(...$elements);
        } else {
            $this->dsDeque->push($inserted);
        }
    }

    public function benchExpansionWithDsVector(array $params): void
    {
        [$default, $inserted, $interval] = $params;
        if ($interval > 1) {
            $count = $this->lastIndex;
            $this->lastIndex += $interval;
            $this->dsVector->allocate($this->lastIndex);
            $elements = \array_fill(0, (int) ($this->lastIndex - $count - 1), $default);
            $elements[] = $inserted;
            $this->dsVector->push(...$elements);
        } else {
            $this->dsVector->push($inserted);
        }
    }

    public function benchExpansionWithSplFixedArray(array $params): void
    {
        [, $inserted, $interval] = $params;
        $this->lastIndex += $interval;
        $this->splFixedArray->setSize($this->lastIndex + 1);
        $this->splFixedArray[$this->lastIndex] = $inserted;
    }
}

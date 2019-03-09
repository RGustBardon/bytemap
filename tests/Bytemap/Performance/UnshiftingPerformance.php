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
 * @Groups({"Time"})
 * @ParamProviders({"providePairs"})
 * @Revs(10000)
 *
 * @internal
 */
final class UnshiftingPerformance extends AbstractTestOfPerformance
{
    private /* int */ $lastIndex = 0;
    
    public function benchUnshiftingWithArray(array $params): void
    {
        [, $inserted] = $params;
        \array_unshift($this->array, $inserted);
    }
    
    public function benchUnshiftingWithBytemap(array $params): void
    {
        [, $inserted] = $params;
        $this->bytemap->insert([$inserted], 0);
    }
    
    public function benchUnshiftingWithDsDeque(array $params): void
    {
        [, $inserted] = $params;
        $this->dsDeque->unshift($inserted);
    }
    
    public function benchUnshiftingWithDsVector(array $params): void
    {
        [, $inserted] = $params;
        $this->dsVector->unshift($inserted);
    }
    
    public function benchUnshiftingWithRecreatedSplFixedArray(array $params): void
    {
        [, $inserted] = $params;
        $elements = $this->splFixedArray->toArray();
        \array_unshift($elements, $inserted);
        $this->splFixedArray = \SplFixedArray::fromArray($elements, false);
    }
    
    public function benchUnshiftingWithInPlaceSplFixedArray(array $params): void
    {
        [, $inserted] = $params;
        $this->splFixedArray->setSize(++$this->lastIndex);
        
        // Shift all the subsequent elements right.
        for ($i = $this->lastIndex - 1; $i >= 1; --$i) {
            $this->splFixedArray[$i] = $this->splFixedArray[$i - 1];
        }
        
        $this->splFixedArray[0] = $inserted;
    }
}

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
 * @BeforeMethods({"setUpFilledContainers"})
 * @Groups({"Time"})
 * @Revs(10000)
 *
 * @internal
 */
final class ShiftingPerformance extends AbstractTestOfPerformance
{
    protected const CONTAINER_ELEMENT_COUNT = 10000;

    private /* int */ $elementCount = self::CONTAINER_ELEMENT_COUNT;

    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchShiftingWithArray(array $params): void
    {
        unset($this->array[0]);
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchShiftingWithBytemap(array $params): void
    {
        unset($this->bytemap[0]);
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchShiftingWithDsDeque(array $params): void
    {
        $this->dsDeque->shift();
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchShiftingWithDsVector(array $params): void
    {
        $this->dsVector->shift();
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchShiftingWithRecreatedSplFixedArray(array $params): void
    {
        $elements = $this->splFixedArray->toArray();
        unset($elements[0]);
        $this->splFixedArray = \SplFixedArray::fromArray($elements, false);
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchShiftingWithInPlaceSplFixedArray(array $params): void
    {
        // Shift all the subsequent elements left.
        for ($i = 1; $i < $this->elementCount; ++$i) {
            $this->splFixedArray[$i - 1] = $this->splFixedArray[$i];
        }

        // Delete the last element.
        $this->splFixedArray->setSize(--$this->elementCount);
    }
}

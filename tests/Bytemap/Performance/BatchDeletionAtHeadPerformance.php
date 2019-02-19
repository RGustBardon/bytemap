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
 * @Revs(100)
 *
 * @internal
 */
final class BatchDeletionAtHeadPerformance extends AbstractTestOfPerformance
{
    private const MAXIMUM_BATCH_ELEMENT_COUNT = 1000;

    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchBatchDeletionAtHeadWithArray(array $params): void
    {
        \array_splice($this->array, 0, \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT));
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchBatchDeletionAtHeadWithBytemap(array $params): void
    {
        $this->bytemap->delete(0, \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT));
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchBatchDeletionAtHeadWithDsDeque(array $params): void
    {
        $this->dsDeque = $this->dsDeque->slice(\mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT));
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchBatchDeletionAtHeadWithDsVector(array $params): void
    {
        $this->dsVector = $this->dsVector->slice(\mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT));
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchBatchDeletionAtHeadWithRecreatedSplFixedArray(array $params): void
    {
        $this->splFixedArray = \SplFixedArray::fromArray(\array_slice($this->splFixedArray->toArray(), \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT)), false);
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchBatchDeletionAtHeadWithInPlaceSplFixedArray(array $params): void
    {
        $howMany = \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT);

        // Shift all the subsequent elements left by the number of elements deleted.
        for ($i = $howMany, $elementCount = \count($this->splFixedArray); $i < $elementCount; ++$i) {
            $this->splFixedArray[$i - $howMany] = $this->splFixedArray[$i];
        }

        // Delete the trailing elements.
        $this->splFixedArray->setSize($elementCount - $howMany);
    }
}

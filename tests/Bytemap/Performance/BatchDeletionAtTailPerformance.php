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
final class BatchDeletionAtTailPerformance extends AbstractTestOfPerformance
{
    private const MAXIMUM_BATCH_ELEMENT_COUNT = 1000;

    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchBatchDeletionAtTailWithArray(array $params): void
    {
        \array_splice($this->array, -\mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT));
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchBatchDeletionAtTailWithBytemap(array $params): void
    {
        $this->bytemap->delete(-\mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT));
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchBatchDeletionAtTailWithDsDeque(array $params): void
    {
        for ($i = \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT); $i > 0; --$i) {
            $this->dsDeque->pop();
        }
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchBatchDeletionAtTailWithDsVector(array $params): void
    {
        for ($i = \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT); $i > 0; --$i) {
            $this->dsVector->pop();
        }
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchBatchDeletionAtTailWithSplFixedArray(array $params): void
    {
        $this->splFixedArray->setSize(\count($this->splFixedArray) - \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT));
    }
}

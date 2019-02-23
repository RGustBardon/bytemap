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
 * @Revs(1000)
 *
 * @internal
 */
final class BatchInsertionAtTailPerformance extends AbstractTestOfPerformance
{
    private /* int */ $elementCycleCount;

    private /* array */ $insertedElements = [];

    public function setUp(array $params): void
    {
        parent::setUp($params);

        [, $inserted] = $params;

        for ($i = \ord(' '), $final = \ord('~'); $i <= $final; ++$i) {
            $element = $inserted;
            $element[0] = \chr($i);
            $this->insertedElements[] = $element;
        }
        $this->elementCycleCount = \count($this->insertedElements);
    }

    public function benchBatchInsertionAtTailWithArray(array $params): void
    {
        \array_push($this->array, ...\array_slice($this->insertedElements, 0, \mt_rand(1, $this->elementCycleCount)));
    }

    public function benchBatchInsertionAtTailWithBytemap(array $params): void
    {
        $this->bytemap->insert(\array_slice($this->insertedElements, 0, \mt_rand(1, $this->elementCycleCount)));
    }

    public function benchBatchInsertionAtTailWithDsDeque(array $params): void
    {
        $this->dsDeque->push(...\array_slice($this->insertedElements, 0, \mt_rand(1, $this->elementCycleCount)));
    }

    public function benchBatchInsertionAtTailWithDsVector(array $params): void
    {
        $this->dsVector->push(...\array_slice($this->insertedElements, 0, \mt_rand(1, $this->elementCycleCount)));
    }

    public function benchBatchInsertionAtTailSplFixedArray(array $params): void
    {
        $insertedElements = \array_slice($this->insertedElements, 0, \mt_rand(1, $this->elementCycleCount));
        $elementCount = \count($this->splFixedArray);
        $this->splFixedArray->setSize($elementCount + \count($insertedElements));
        foreach ($insertedElements as $element) {
            $this->splFixedArray[$elementCount++] = $element;
        }
    }
}

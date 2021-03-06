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
 * @Revs(100)
 *
 * @internal
 */
final class BatchInsertionAtHeadPerformance extends AbstractTestOfPerformance
{
    private const MAXIMUM_BATCH_ELEMENT_COUNT = 1000;

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

        $elementCycleCount = \count($this->insertedElements);
        for ($i = $elementCycleCount; $i < self::MAXIMUM_BATCH_ELEMENT_COUNT; ++$i) {
            $this->insertedElements[] = $this->insertedElements[$i % $elementCycleCount];
        }
    }

    public function benchBatchInsertionAtHeadWithArray(array $params): void
    {
        \array_unshift($this->array, ...\array_slice($this->insertedElements, 0, \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT)));
    }

    public function benchBatchInsertionAtHeadWithBytemap(array $params): void
    {
        $this->bytemap->insert(\array_slice($this->insertedElements, 0, \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT)), 0);
    }

    public function benchBatchInsertionAtHeadWithDsDeque(array $params): void
    {
        $this->dsDeque->unshift(...\array_slice($this->insertedElements, 0, \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT)));
    }

    public function benchBatchInsertionAtHeadWithDsVector(array $params): void
    {
        $this->dsVector->unshift(...\array_slice($this->insertedElements, 0, \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT)));
    }

    public function benchBatchInsertionAtHeadWithRecreatedSplFixedArray(array $params): void
    {
        $insertedElements = \array_slice($this->insertedElements, 0, \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT));
        if (\count($this->splFixedArray) > 0) {
            \array_push($insertedElements, ...$this->splFixedArray->toArray());
        }
        $this->splFixedArray = \SplFixedArray::fromArray($insertedElements, false);
    }

    public function benchBatchInsertionAtHeadWithInPlaceSplFixedArray(array $params): void
    {
        $insertedElements = \array_slice($this->insertedElements, 0, \mt_rand(1, self::MAXIMUM_BATCH_ELEMENT_COUNT));

        // Resize.
        $originalSize = \count($this->splFixedArray);
        $newSize = $originalSize + \count($insertedElements);
        $this->splFixedArray->setSize($newSize);

        // Append.
        for ($i = 0; $i < $newSize - $originalSize; ++$i) {
            $this->splFixedArray[$originalSize + $i] = $insertedElements[$i];
        }

        // Juggle.
        $n = $newSize;
        $shift = $originalSize;

        while (0 !== $n) {
            $tmp = $n;
            $n = $shift % $n;
            $shift = $tmp;
        }
        $gcd = $shift;

        $n = $newSize;
        $shift = $originalSize;

        for ($i = 0; $i < $gcd; ++$i) {
            $tmp = $this->splFixedArray[$i];
            $j = $i;
            while (true) {
                $k = $j + $shift;
                if ($k >= $n) {
                    $k -= $n;
                }
                if ($k === $i) {
                    break;
                }
                $this->splFixedArray[$j] = $this->splFixedArray[$k];
                $j = $k;
            }
            $this->splFixedArray[$j] = $tmp;
        }
    }
}

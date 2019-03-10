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
 * @AfterMethods({"tearDown"})
 * @Groups({"Time"})
 * @Iterations(5)
 *
 * @internal
 */
final class FindingFirstIndexOfElementPerformance extends AbstractTestOfPerformance
{
    private /* int */ $expectedIndex;

    private /* int */ $actualIndex;

    private /* string */ $soughtElement;

    public function tearDown(array $params): void
    {
        \assert($this->expectedIndex === $this->actualIndex);
    }

    public function setUpFilledContainers(array $params): void
    {
        parent::setUpFilledContainers($params);

        [, $this->soughtElement, $dataStructure] = $params;

        $this->soughtElement[0] = "\xff";

        $this->expectedIndex = \intdiv(self::CONTAINER_ELEMENT_COUNT, 2);

        switch ($dataStructure) {
            case self::DATA_STRUCTURE_ARRAY:
                $this->array[$this->expectedIndex] = $this->soughtElement;

                break;
            case self::DATA_STRUCTURE_BYTEMAP:
                $this->bytemap[$this->expectedIndex] = $this->soughtElement;

                break;
            case self::DATA_STRUCTURE_DS_DEQUE:
                $this->dsDeque[$this->expectedIndex] = $this->soughtElement;

                break;
            case self::DATA_STRUCTURE_DS_VECTOR:
                $this->dsVector[$this->expectedIndex] = $this->soughtElement;

                break;
            case self::DATA_STRUCTURE_SPL_FIXED_ARRAY:
                $this->splFixedArray[$this->expectedIndex] = $this->soughtElement;

                break;
            default:
                throw new \DomainException('Unsupported data structure');
        }
    }

    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchFindingFirstIndexOfElementWithArray(array $params): void
    {
        $this->actualIndex = \array_search($this->soughtElement, $this->array, true);
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchFindingFirstIndexOfElementWithBytemap(array $params): void
    {
        foreach ($this->bytemap->find([$this->soughtElement], true, 1) as $index => $element) {
            $this->actualIndex = $index;
        }
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchFindingFirstIndexOfElementWithDsDeque(array $params): void
    {
        $this->actualIndex = $this->dsDeque->find($this->soughtElement);
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchFindingFirstIndexOfElementWithDsVector(array $params): void
    {
        $this->actualIndex = $this->dsVector->find($this->soughtElement);
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchFindingFirstIndexOfElementWithSplFixedArray(array $params): void
    {
        foreach ($this->splFixedArray as $index => $element) {
            if ($this->soughtElement === $element) {
                $this->actualIndex = $index;

                break;
            }
        }
    }
}

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
final class CheckingExistenceOfAnyElementPerformance extends AbstractTestOfPerformance
{
    private const SOUGHT_ELEMENT_COUNT = 100;

    private /* bool */ $exists = false;

    private /* array */ $soughtElements = [];

    public function tearDown(array $params): void
    {
        \assert(true === $this->exists);
    }

    public function setUpFilledContainers(array $params): void
    {
        parent::setUpFilledContainers($params);

        [, $inserted, $dataStructure] = $params;

        for ($i = 0; $i < self::SOUGHT_ELEMENT_COUNT; ++$i) {
            $this->soughtElements[] = \chr(0xff - $i).\substr($inserted, 1);
        }

        $indexOfExistingElement = \intdiv(self::CONTAINER_ELEMENT_COUNT, 2);
        $existingElement = $this->soughtElements[\intdiv(self::SOUGHT_ELEMENT_COUNT, 2)];

        switch ($dataStructure) {
            case self::DATA_STRUCTURE_ARRAY:
                $this->array[$indexOfExistingElement] = $existingElement;

                break;
            case self::DATA_STRUCTURE_BYTEMAP:
                $this->bytemap[$indexOfExistingElement] = $existingElement;

                break;
            case self::DATA_STRUCTURE_DS_DEQUE:
                $this->dsDeque[$indexOfExistingElement] = $existingElement;

                break;
            case self::DATA_STRUCTURE_DS_VECTOR:
                $this->dsVector[$indexOfExistingElement] = $existingElement;

                break;
            case self::DATA_STRUCTURE_SPL_FIXED_ARRAY:
                $this->splFixedArray[$indexOfExistingElement] = $existingElement;

                break;
            default:
                throw new \DomainException('Unsupported data structure');
        }
    }

    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchCheckingExistenceOfAnyElementWithArray(array $params): void
    {
        $soughtElements = \array_fill_keys($this->soughtElements, true);
        foreach ($this->array as $element) {
            if (isset($soughtElements[$element])) {
                $this->exists = true;
                break;
            }
        }
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchCheckingExistenceOfAnyElementWithBytemap(array $params): void
    {
        foreach ($this->bytemap->find($this->soughtElements, true, 1) as $element) {
            $this->exists = true;
        }
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchCheckingExistenceOfAnyElementWithDsDeque(array $params): void
    {
        $soughtElements = \array_fill_keys($this->soughtElements, true);
        foreach ($this->dsDeque as $element) {
            if (isset($soughtElements[$element])) {
                $this->exists = true;
                break;
            }
        }
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchCheckingExistenceOfAnyElementWithDsVector(array $params): void
    {
        $soughtElements = \array_fill_keys($this->soughtElements, true);
        foreach ($this->dsVector as $element) {
            if (isset($soughtElements[$element])) {
                $this->exists = true;
                break;
            }
        }
        
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchCheckingExistenceOfAnyElementWithSplFixedArray(array $params): void
    {
        $soughtElements = \array_fill_keys($this->soughtElements, true);
        foreach ($this->splFixedArray as $element) {
            if (isset($soughtElements[$element])) {
                $this->exists = true;
                break;
            }
        }
    }
}

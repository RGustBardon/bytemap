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
final class GreppingAllElementsPerformance extends AbstractTestOfPerformance
{
    private const SOUGHT_ELEMENT_COUNT = 100;

    private const SOUGHT_ELEMENT_CYCLE_COUNT = 10;

    private /* array */ $expectedIndexes = [];

    private /* array */ $actualIndexes = [];

    private /* array */ $soughtElements = [];

    private /* string */ $regularExpression;

    public function tearDown(array $params): void
    {
        \assert($this->expectedIndexes === $this->actualIndexes);
    }

    public function setUpFilledContainers(array $params): void
    {
        parent::setUpFilledContainers($params);

        [, $inserted, $dataStructure] = $params;

        $indexOfExistingElement = 0;
        for ($i = 0; $i < self::SOUGHT_ELEMENT_COUNT; ++$i) {
            $this->soughtElements[] = \chr(0xff - ($i % self::SOUGHT_ELEMENT_CYCLE_COUNT)).\substr($inserted, 1);

            $indexOfExistingElement += \intdiv(self::CONTAINER_ELEMENT_COUNT, self::SOUGHT_ELEMENT_COUNT + 1);
            $existingElement = $this->soughtElements[$i % self::SOUGHT_ELEMENT_CYCLE_COUNT];

            if (!isset($this->expectedIndexes[$existingElement])) {
                $this->expectedIndexes[$existingElement] = [];
            }

            $this->expectedIndexes[$existingElement][] = $indexOfExistingElement;

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

        $this->actualIndexes = \array_fill_keys(\array_keys($this->expectedIndexes), []);

        $this->regularExpression = '~^(?:'.\implode('|', \array_unique($this->soughtElements)).')~';
    }

    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchGreppingAllElementsWithArray(array $params): void
    {
        foreach (\preg_grep($this->regularExpression, $this->array) as $index => $element) {
            $this->actualIndexes[$element][] = $index;
        }
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchGreppingAllElementsWithBytemap(array $params): void
    {
        foreach ($this->bytemap->grep([$this->regularExpression]) as $index => $element) {
            $this->actualIndexes[$element][] = $index;
        }
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchGreppingAllElementsWithDsDeque(array $params): void
    {
        [$default, , ] = $params;
        if (1 === \strlen($default)) {
            $whitelist = \array_fill_keys($this->soughtElements, true);
            foreach ($this->dsDeque as $index => $element) {
                if (isset($whitelist[$element])) {
                    $this->actualIndexes[$element][] = $index;
                }
            }
        } else {
            foreach (\preg_grep($this->regularExpression, $this->dsDeque->toArray()) as $index => $element) {
                $this->actualIndexes[$element][] = $index;
            }
        }
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchGreppingAllElementsWithDsVector(array $params): void
    {
        [$default, , ] = $params;
        if (1 === \strlen($default)) {
            $whitelist = \array_fill_keys($this->soughtElements, true);
            foreach ($this->dsVector as $index => $element) {
                if (isset($whitelist[$element])) {
                    $this->actualIndexes[$element][] = $index;
                }
            }
        } else {
            foreach (\preg_grep($this->regularExpression, $this->dsVector->toArray()) as $index => $element) {
                $this->actualIndexes[$element][] = $index;
            }
        }
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchGreppingAllElementsWithSplFixedArray(array $params): void
    {
        [$default, , ] = $params;
        if (1 === \strlen($default)) {
            $whitelist = \array_fill_keys($this->soughtElements, true);
            foreach ($this->splFixedArray as $index => $element) {
                if (isset($whitelist[$element])) {
                    $this->actualIndexes[$element][] = $index;
                }
            }
        } else {
            foreach (\preg_grep($this->regularExpression, $this->splFixedArray->toArray()) as $index => $element) {
                $this->actualIndexes[$element][] = $index;
            }
        }
    }
}

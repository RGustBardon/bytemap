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

use Bytemap\Bytemap;
use Bytemap\BytemapInterface;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @AfterMethods({"tearDown"})
 *
 * @internal
 */
final class IterationPerformance extends AbstractTestOfPerformance
{
    private const CONTAINER_ELEMENT_COUNT = 100000;

    private const DATA_STRUCTURE_ARRAY = 'array';
    private const DATA_STRUCTURE_BYTEMAP = BytemapInterface::class;
    private const DATA_STRUCTURE_DS_DEQUE = \Ds\Deque::class;
    private const DATA_STRUCTURE_DS_VECTOR = \Ds\Vector::class;
    private const DATA_STRUCTURE_SPL_FIXED_ARRAY = \SplFixedArray::class;

    private const DATA_STRUCTURES = [
        self::DATA_STRUCTURE_ARRAY,
        self::DATA_STRUCTURE_BYTEMAP,
        self::DATA_STRUCTURE_DS_DEQUE,
        self::DATA_STRUCTURE_DS_VECTOR,
        self::DATA_STRUCTURE_SPL_FIXED_ARRAY,
    ];

    private /* int */ $elementCount;

    private /* array */ $array;

    private /* BytemapInterface */ $bytemap;

    private /* \Ds\Sequence */ $dsDeque;

    private /* \Ds\Vector */ $dsVector;

    private /* \SplFixedArray */ $splFixedArray;

    public function setUp(array $params): void
    {
        [$default, $dataStructure] = $params;

        $this->elementCount = 0;

        $this->array = [];
        $this->bytemap = new Bytemap($default);
        $this->dsDeque = new \Ds\Deque();
        $this->dsVector = new \Ds\Vector();
        $this->splFixedArray = new \SplFixedArray();

        switch ($dataStructure) {
            case self::DATA_STRUCTURE_ARRAY:
                $this->array = \array_fill(0, self::CONTAINER_ELEMENT_COUNT, $default);

                break;
            case self::DATA_STRUCTURE_BYTEMAP:
                $this->bytemap[self::CONTAINER_ELEMENT_COUNT - 1] = $default;

                break;
            case self::DATA_STRUCTURE_DS_DEQUE:
                $this->dsDeque->allocate(self::CONTAINER_ELEMENT_COUNT);
                for ($i = 0; $i < self::CONTAINER_ELEMENT_COUNT; ++$i) {
                    $this->dsDeque->insert($i, $default);
                }

                break;
            case self::DATA_STRUCTURE_DS_VECTOR:
                $this->dsVector->allocate(self::CONTAINER_ELEMENT_COUNT);
                for ($i = 0; $i < self::CONTAINER_ELEMENT_COUNT; ++$i) {
                    $this->dsVector->insert($i, $default);
                }

                break;
            case self::DATA_STRUCTURE_SPL_FIXED_ARRAY:
                $this->splFixedArray->setSize(self::CONTAINER_ELEMENT_COUNT);
                for ($i = 0; $i < self::CONTAINER_ELEMENT_COUNT; ++$i) {
                    $this->splFixedArray[$i] = $default;
                }

                break;
            default:
                throw new \DomainException('Unsupported data structure');
        }

        \mt_srand(0);
    }

    public function tearDown(array $params): void
    {
        \assert(self::CONTAINER_ELEMENT_COUNT === $this->elementCount);
    }

    public function providePairsAndArray(): \Generator
    {
        $dataStructure = self::DATA_STRUCTURE_ARRAY;
        foreach (self::DEFAULT_INSERTED_PAIRS as [$default]) {
            yield \sprintf('elementLength:%d dataStructure:%s', \strlen($default), $dataStructure) => [$default, $dataStructure];
        }
    }

    public function providePairsAndBytemap(): \Generator
    {
        $dataStructure = self::DATA_STRUCTURE_BYTEMAP;
        foreach (self::DEFAULT_INSERTED_PAIRS as [$default]) {
            yield \sprintf('elementLength:%d dataStructure:%s', \strlen($default), $dataStructure) => [$default, $dataStructure];
        }
    }

    public function providePairsAndDsDeque(): \Generator
    {
        $dataStructure = self::DATA_STRUCTURE_DS_DEQUE;
        foreach (self::DEFAULT_INSERTED_PAIRS as [$default]) {
            yield \sprintf('elementLength:%d dataStructure:%s', \strlen($default), $dataStructure) => [$default, $dataStructure];
        }
    }

    public function providePairsAndDsVector(): \Generator
    {
        $dataStructure = self::DATA_STRUCTURE_DS_VECTOR;
        foreach (self::DEFAULT_INSERTED_PAIRS as [$default]) {
            yield \sprintf('elementLength:%d dataStructure:%s', \strlen($default), $dataStructure) => [$default, $dataStructure];
        }
    }

    public function providePairsAndSplFixedArray(): \Generator
    {
        $dataStructure = self::DATA_STRUCTURE_SPL_FIXED_ARRAY;
        foreach (self::DEFAULT_INSERTED_PAIRS as [$default]) {
            yield \sprintf('elementLength:%d dataStructure:%s', \strlen($default), $dataStructure) => [$default, $dataStructure];
        }
    }

    /**
     * @Groups({"Time"})
     * @ParamProviders({"providePairsAndArray"})
     * @Iterations(5)
     */
    public function benchIterationWithArray(array $params): void
    {
        foreach ($this->array as $element) {
            ++$this->elementCount;
        }
    }

    /**
     * @Groups({"Time"})
     * @ParamProviders({"providePairsAndBytemap"})
     * @Iterations(5)
     */
    public function benchIterationWithBytemap(array $params): void
    {
        foreach ($this->bytemap as $element) {
            ++$this->elementCount;
        }
    }

    /**
     * @Groups({"Time"})
     * @ParamProviders({"providePairsAndDsDeque"})
     * @Iterations(5)
     */
    public function benchIterationWithDsDeque(array $params): void
    {
        foreach ($this->dsDeque as $element) {
            ++$this->elementCount;
        }
    }

    /**
     * @Groups({"Time"})
     * @ParamProviders({"providePairsAndDsVector"})
     * @Iterations(5)
     */
    public function benchIterationWithDsVector(array $params): void
    {
        foreach ($this->dsVector as $element) {
            ++$this->elementCount;
        }
    }

    /**
     * @Groups({"time"})
     * @ParamProviders({"providePairsAndSplFixedArray"})
     * @Iterations(5)
     */
    public function benchIterationWithSplFixedArray(array $params): void
    {
        foreach ($this->splFixedArray as $element) {
            ++$this->elementCount;
        }
    }
}

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
 * @BeforeMethods({"setUp"})
 *
 * @internal
 */
abstract class AbstractTestOfPerformance
{
    protected const CONTAINER_ELEMENT_COUNT = 100000;

    private const DEFAULT_INSERTED_PAIRS = [
        [self::DEFAULT_ELEMENT_SHORT, self::INSERTED_ELEMENT_SHORT],
        [self::DEFAULT_ELEMENT_LONG, self::INSERTED_ELEMENT_LONG],
    ];

    private const DATA_STRUCTURE_ARRAY = 'array';
    private const DATA_STRUCTURE_BYTEMAP = BytemapInterface::class;
    private const DATA_STRUCTURE_DS_DEQUE = \Ds\Deque::class;
    private const DATA_STRUCTURE_DS_VECTOR = \Ds\Vector::class;
    private const DATA_STRUCTURE_SPL_FIXED_ARRAY = \SplFixedArray::class;

    private const DEFAULT_ELEMENT_SHORT = "\x0";
    private const DEFAULT_ELEMENT_LONG = "\x0\x0\x0\x0";

    private const INSERTED_ELEMENT_SHORT = "\x1";
    private const INSERTED_ELEMENT_LONG = "\x1\x2\x3\x4";

    private const INTERVALS = [1, 10, 100];

    private const DATA_STRUCTURES = [
        self::DATA_STRUCTURE_ARRAY,
        self::DATA_STRUCTURE_BYTEMAP,
        self::DATA_STRUCTURE_DS_DEQUE,
        self::DATA_STRUCTURE_DS_VECTOR,
        self::DATA_STRUCTURE_SPL_FIXED_ARRAY,
    ];

    protected /* array */ $array;

    protected /* BytemapInterface */ $bytemap;

    protected /* \Ds\Sequence */ $dsDeque;

    protected /* \Ds\Vector */ $dsVector;

    protected /* \SplFixedArray */ $splFixedArray;

    public function setUp(array $params): void
    {
        $this->array = [];
        $this->bytemap = new Bytemap(\reset($params));
        $this->dsDeque = new \Ds\Deque();
        $this->dsVector = new \Ds\Vector();
        $this->splFixedArray = new \SplFixedArray();

        \mt_srand(0);
    }

    public function setUpFilledContainers(array $params): void
    {
        $this->setUp($params);

        [$default, , $dataStructure] = $params;

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

                break;
            default:
                throw new \DomainException('Unsupported data structure');
        }
    }

    public static function providePairs(): \Generator
    {
        foreach (self::DEFAULT_INSERTED_PAIRS as [$default, $inserted]) {
            yield \sprintf('elementLength:%d', \strlen($default)) => [$default, $inserted];
        }
    }

    public static function providePairsAndArray(): \Generator
    {
        yield from self::providePairsAndContainer(self::DATA_STRUCTURE_ARRAY);
    }

    public static function providePairsAndBytemap(): \Generator
    {
        yield from self::providePairsAndContainer(self::DATA_STRUCTURE_BYTEMAP);
    }

    public static function providePairsAndDsDeque(): \Generator
    {
        yield from self::providePairsAndContainer(self::DATA_STRUCTURE_DS_DEQUE);
    }

    public static function providePairsAndDsVector(): \Generator
    {
        yield from self::providePairsAndContainer(self::DATA_STRUCTURE_DS_VECTOR);
    }

    public static function providePairsAndSplFixedArray(): \Generator
    {
        yield from self::providePairsAndContainer(self::DATA_STRUCTURE_SPL_FIXED_ARRAY);
    }

    public static function providePairsAndIntervals(): \Generator
    {
        foreach (self::providePairs() as $key => [$default, $inserted]) {
            foreach (self::INTERVALS as $interval) {
                yield $key.\sprintf(' maximumGap:%d', $interval) => [$default, $inserted, $interval];
            }
        }
    }

    private static function providePairsAndContainer(string $dataStructure): \Generator
    {
        foreach (self::providePairs() as $key => [$default, $inserted]) {
            yield $key.\sprintf(' dataStructure:%s', $dataStructure) => [$default, $inserted, $dataStructure];
        }
    }
}

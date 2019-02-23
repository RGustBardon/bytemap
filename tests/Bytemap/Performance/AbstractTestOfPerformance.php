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
    protected const CONTAINER_ELEMENT_CYCLE_COUNT = 50;

    protected const DATA_STRUCTURE_ARRAY = 'array';
    protected const DATA_STRUCTURE_BYTEMAP = BytemapInterface::class;
    protected const DATA_STRUCTURE_DS_DEQUE = \Ds\Deque::class;
    protected const DATA_STRUCTURE_DS_VECTOR = \Ds\Vector::class;
    protected const DATA_STRUCTURE_SPL_FIXED_ARRAY = \SplFixedArray::class;

    private const DEFAULT_INSERTED_PAIRS = [
        [self::DEFAULT_ELEMENT_SHORT, self::INSERTED_ELEMENT_SHORT],
        [self::DEFAULT_ELEMENT_LONG, self::INSERTED_ELEMENT_LONG],
    ];

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
        static $packFormats = [
            1 => 'C',
            2 => 'v',
            3 => 'VX',
            4 => 'V',
            5 => 'PXXX',
            6 => 'PXX',
            7 => 'PX',
            8 => 'P',
        ];

        $this->setUp($params);

        [$default, , $dataStructure] = $params;

        $packFormat = $packFormats[\strlen($default)];
        $cachePath = \sprintf(
            '%s/benchmark-%s-%d-%d-%d.ser',
            \sys_get_temp_dir(),
            \strtr($dataStructure, '\\', '-'),
            \strlen($default),
            static::CONTAINER_ELEMENT_COUNT,
            static::CONTAINER_ELEMENT_CYCLE_COUNT
        );
        if (\file_exists($cachePath)) {
            $serialized = \file_get_contents($cachePath);
            \assert(false !== $serialized);
        }

        switch ($dataStructure) {
            case self::DATA_STRUCTURE_ARRAY:
                if (isset($serialized) && \is_string($serialized)) {
                    $this->array = \unserialize($serialized, ['allowed_classes' => false]);
                    \assert(false !== $this->array);
                } else {
                    for ($i = 0; $i < static::CONTAINER_ELEMENT_COUNT; ++$i) {
                        $this->array[] = \pack($packFormat, $i % static::CONTAINER_ELEMENT_CYCLE_COUNT);
                    }
                    $serialized = \serialize($this->array);
                    \file_put_contents($cachePath, $serialized);
                }

                break;
            case self::DATA_STRUCTURE_BYTEMAP:
                if (isset($serialized) && \is_string($serialized)) {
                    $this->bytemap = \unserialize($serialized, ['allowed_classes' => [Bytemap::class]]);
                    \assert(false !== $this->bytemap);
                } else {
                    $this->bytemap[static::CONTAINER_ELEMENT_COUNT - 1] = $default;
                    for ($i = 0; $i < static::CONTAINER_ELEMENT_COUNT; ++$i) {
                        $this->bytemap[$i] = \pack($packFormat, $i % static::CONTAINER_ELEMENT_CYCLE_COUNT);
                    }
                    $serialized = \serialize($this->bytemap);
                    \file_put_contents($cachePath, $serialized);
                }

                break;
            case self::DATA_STRUCTURE_DS_DEQUE:
                if (isset($serialized) && \is_string($serialized)) {
                    $unserialized = @\unserialize($serialized, ['allowed_classes' => [\Ds\Deque::class]]);
                    if (false !== $unserialized) {
                        // ext-ds was not toggled between serialization and unserialization.
                        $this->dsDeque = $unserialized;
                    }
                }

                if (!isset($unserialized) || false === $unserialized) {
                    $this->dsDeque->allocate(static::CONTAINER_ELEMENT_COUNT);
                    for ($i = 0; $i < static::CONTAINER_ELEMENT_COUNT; ++$i) {
                        $this->dsDeque[] = \pack($packFormat, $i % static::CONTAINER_ELEMENT_CYCLE_COUNT);
                    }
                    $serialized = \serialize($this->dsDeque);
                    \file_put_contents($cachePath, $serialized);
                }

                break;
            case self::DATA_STRUCTURE_DS_VECTOR:
                if (isset($serialized) && \is_string($serialized)) {
                    $unserialized = @\unserialize($serialized, ['allowed_classes' => [\Ds\Vector::class]]);
                    if (false !== $unserialized) {
                        // ext-ds was not toggled between serialization and unserialization.
                        $this->dsVector = $unserialized;
                    }
                }

                if (!isset($unserialized) || false === $unserialized) {
                    $this->dsVector->allocate(static::CONTAINER_ELEMENT_COUNT);
                    for ($i = 0; $i < static::CONTAINER_ELEMENT_COUNT; ++$i) {
                        $this->dsVector[] = \pack($packFormat, $i % static::CONTAINER_ELEMENT_CYCLE_COUNT);
                    }
                    $serialized = \serialize($this->dsVector);
                    \file_put_contents($cachePath, $serialized);
                }

                break;
            case self::DATA_STRUCTURE_SPL_FIXED_ARRAY:
                if (isset($serialized) && \is_string($serialized)) {
                    $this->splFixedArray = \unserialize($serialized, ['allowed_classes' => [\SplFixedArray::class]]);
                    \assert(false !== $this->splFixedArray);
                    $this->splFixedArray->__wakeup();
                } else {
                    $this->splFixedArray->setSize(static::CONTAINER_ELEMENT_COUNT);
                    for ($i = 0; $i < static::CONTAINER_ELEMENT_COUNT; ++$i) {
                        $this->splFixedArray[$i] = \pack($packFormat, $i % static::CONTAINER_ELEMENT_CYCLE_COUNT);
                    }
                    $serialized = \serialize($this->splFixedArray);
                    \file_put_contents($cachePath, $serialized);
                }

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

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

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @BeforeMethods({"setUpFilledContainers"})
 * @Groups({"Time"})
 * @Iterations(5)
 *
 * @internal
 */
final class UnserializationPerformance extends AbstractTestOfPerformance
{
    private /* string */ $serializedArray;

    private /* string */ $serializedBytemap;

    private /* string */ $serializedDsDeque;

    private /* string */ $serializedDsVector;

    private /* string */ $serializedSplFixedArray;

    public function setUpFilledContainers(array $params): void
    {
        parent::setUpFilledContainers($params);

        $this->serializedArray = \serialize($this->array);
        $this->serializedBytemap = \serialize($this->bytemap);
        $this->serializedDsDeque = \serialize($this->dsDeque);
        $this->serializedDsVector = \serialize($this->dsVector);
        $this->serializedSplFixedArray = \serialize($this->splFixedArray);
    }

    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchUnserializationWithArray(array $params): void
    {
        \unserialize($this->serializedArray, ['allowed_classes' => false]);
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchUnserializationWithBytemap(array $params): void
    {
        \unserialize($this->serializedBytemap, ['allowed_classes' => [Bytemap::class]]);
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchUnserializationWithDsDeque(array $params): void
    {
        \unserialize($this->serializedDsDeque, ['allowed_classes' => [\Ds\Deque::class]]);
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchUnserializationWithDsVector(array $params): void
    {
        \unserialize($this->serializedDsVector, ['allowed_classes' => [\Ds\Vector::class]]);
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchUnserializationWithSplFixedArray(array $params): void
    {
        \unserialize($this->serializedSplFixedArray, ['allowed_classes' => [\SplFixedArray::class]])->__wakeup();
    }
}

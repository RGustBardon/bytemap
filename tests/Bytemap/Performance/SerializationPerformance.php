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
 * @Iterations(5)
 *
 * @internal
 */
final class SerializationPerformance extends AbstractTestOfPerformance
{
    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchSerializationWithArray(array $params): void
    {
        \serialize($this->array);
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchSerializationWithBytemap(array $params): void
    {
        \serialize($this->bytemap);
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchSerializationWithDsDeque(array $params): void
    {
        \serialize($this->dsDeque);
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchSerializationWithDsVector(array $params): void
    {
        \serialize($this->dsVector);
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchSerializationWithSplFixedArray(array $params): void
    {
        \serialize($this->splFixedArray);
    }
}

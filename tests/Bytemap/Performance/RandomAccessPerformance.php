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
 * @Revs(100000)
 *
 * @internal
 */
final class RandomAccessPerformance extends AbstractTestOfPerformance
{
    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchRandomAccessWithArray(array $params): void
    {
        $this->array[\mt_rand(0, self::CONTAINER_ELEMENT_COUNT - 1)];
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchRandomAccessWithBytemap(array $params): void
    {
        $this->bytemap[\mt_rand(0, self::CONTAINER_ELEMENT_COUNT - 1)];
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchRandomAccessWithDsDeque(array $params): void
    {
        $this->dsDeque[\mt_rand(0, self::CONTAINER_ELEMENT_COUNT - 1)];
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchRandomAccessWithDsVector(array $params): void
    {
        $this->dsVector[\mt_rand(0, self::CONTAINER_ELEMENT_COUNT - 1)];
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchRandomAccessWithSplFixedArray(array $params): void
    {
        $this->splFixedArray[\mt_rand(0, self::CONTAINER_ELEMENT_COUNT - 1)];
    }
}

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
final class OverwritingPerformance extends AbstractTestOfPerformance
{
    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchOverwritingWithArray(array $params): void
    {
        [, $inserted, ] = $params;
        $this->array[\mt_rand(0, self::CONTAINER_ELEMENT_COUNT - 1)] = $inserted;
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchOverwritingWithBytemap(array $params): void
    {
        [, $inserted, ] = $params;
        $this->bytemap[\mt_rand(0, self::CONTAINER_ELEMENT_COUNT - 1)] = $inserted;
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchOverwritingWithDsDeque(array $params): void
    {
        [, $inserted, ] = $params;
        $this->dsDeque[\mt_rand(0, self::CONTAINER_ELEMENT_COUNT - 1)] = $inserted;
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchOverwritingWithDsVector(array $params): void
    {
        [, $inserted, ] = $params;
        $this->dsVector[\mt_rand(0, self::CONTAINER_ELEMENT_COUNT - 1)] = $inserted;
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchOverwritingWithSplFixedArray(array $params): void
    {
        [, $inserted, ] = $params;
        $this->splFixedArray[\mt_rand(0, self::CONTAINER_ELEMENT_COUNT - 1)] = $inserted;
    }
}

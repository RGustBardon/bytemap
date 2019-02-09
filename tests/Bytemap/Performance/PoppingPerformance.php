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
final class PoppingPerformance extends AbstractTestOfPerformance
{
    private /* int */ $elementCount = self::CONTAINER_ELEMENT_COUNT;

    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchPoppingWithArray(array $params): void
    {
        unset($this->array[--$this->elementCount]);
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchPoppingWithBytemap(array $params): void
    {
        unset($this->bytemap[--$this->elementCount]);
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchPoppingWithDsDeque(array $params): void
    {
        $this->dsDeque->pop();
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchPoppingWithDsVector(array $params): void
    {
        $this->dsVector->pop();
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchPoppingWithSplFixedArray(array $params): void
    {
        $this->splFixedArray->setSize(--$this->elementCount);
    }
}

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
final class IterationPerformance extends AbstractTestOfPerformance
{
    private /* int */ $elementCount = 0;

    public function tearDown(array $params): void
    {
        \assert(self::CONTAINER_ELEMENT_COUNT === $this->elementCount);
    }

    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchIterationWithArray(array $params): void
    {
        foreach ($this->array as $element) {
            ++$this->elementCount;
        }
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchIterationWithBytemap(array $params): void
    {
        foreach ($this->bytemap as $element) {
            ++$this->elementCount;
        }
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchIterationWithDsDeque(array $params): void
    {
        foreach ($this->dsDeque as $element) {
            ++$this->elementCount;
        }
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchIterationWithDsVector(array $params): void
    {
        foreach ($this->dsVector as $element) {
            ++$this->elementCount;
        }
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchIterationWithSplFixedArray(array $params): void
    {
        foreach ($this->splFixedArray as $element) {
            ++$this->elementCount;
        }
    }
}

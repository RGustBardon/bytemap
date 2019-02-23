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
final class FindingIndexOfMissingElementPerformance extends AbstractTestOfPerformance
{
    private /* bool */ $exists = false;

    private /* string */ $missingElement;

    public function tearDown(array $params): void
    {
        \assert(false === $this->exists);
    }

    public function setUpFilledContainers(array $params): void
    {
        parent::setUpFilledContainers($params);

        [, $this->missingElement, ] = $params;

        $this->missingElement[0] = "\xff";
    }

    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchFindingIndexOfMissingElementWithArray(array $params): void
    {
        $this->exists = \array_search($this->missingElement, $this->array, true);
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchFindingIndexOfMissingElementWithBytemap(array $params): void
    {
        foreach ($this->bytemap->find([$this->missingElement], true, 1) as $index => $element) {
            $this->exists = true;
        }
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchFindingIndexOfMissingElementWithDsDeque(array $params): void
    {
        $this->exists = $this->dsDeque->find($this->missingElement);
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchFindingIndexOfMissingElementWithDsVector(array $params): void
    {
        $this->exists = $this->dsVector->find($this->missingElement);
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchFindingIndexOfMissingElementWithSplFixedArray(array $params): void
    {
        $this->exists = \array_search($this->missingElement, $this->splFixedArray->toArray(), true);
    }
}

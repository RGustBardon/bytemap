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
final class CheckingExistenceOfMissingElementsPerformance extends AbstractTestOfPerformance
{
    private const MISSING_ELEMENT_COUNT = 10;

    private /* bool */ $exists = false;

    private /* array */ $missingElements = [];

    public function tearDown(array $params): void
    {
        \assert(false === $this->exists);
    }

    public function setUpFilledContainers(array $params): void
    {
        parent::setUpFilledContainers($params);

        [, $inserted, ] = $params;

        for ($i = 0; $i < self::MISSING_ELEMENT_COUNT; ++$i) {
            $this->missingElements[] = \chr(0xff - $i).\substr($inserted, 1);
        }
    }

    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchCheckingExistenceOfMissingElementsWithArray(array $params): void
    {
        $this->exists = false;
        $whitelist = \array_fill_keys($this->missingElements, true);
        foreach ($this->array as $element) {
            if (isset($whitelist[$element])) {
                $this->exists = true;

                break;
            }
        }
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchCheckingExistenceOfMissingElementsWithBytemap(array $params): void
    {
        foreach ($this->bytemap->find($this->missingElements, true, 1) as $element) {
            $this->exists = true;
        }
    }

    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchCheckingExistenceOfMissingElementsWithDsDeque(array $params): void
    {
        $this->exists = false;
        $whitelist = \array_fill_keys($this->missingElements, true);
        foreach ($this->dsDeque as $element) {
            if (isset($whitelist[$element])) {
                $this->exists = true;

                break;
            }
        }
    }

    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchCheckingExistenceOfMissingElementsWithDsVector(array $params): void
    {
        $this->exists = false;
        $whitelist = \array_fill_keys($this->missingElements, true);
        foreach ($this->dsVector as $element) {
            if (isset($whitelist[$element])) {
                $this->exists = true;

                break;
            }
        }
    }

    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchCheckingExistenceOfMissingElementsWithSplFixedArray(array $params): void
    {
        $this->exists = false;
        $whitelist = \array_fill_keys($this->missingElements, true);
        foreach ($this->splFixedArray as $element) {
            if (isset($whitelist[$element])) {
                $this->exists = true;

                break;
            }
        }
    }
}

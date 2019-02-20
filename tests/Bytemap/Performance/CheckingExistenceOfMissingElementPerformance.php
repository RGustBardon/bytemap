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
final class CheckingExistenceOfMissingElementPerformance extends AbstractTestOfPerformance
{
    private /* bool */ $exists = false;
    
    private /* string */ $missingElement;
    
    public function setUpFilledContainers(array $params): void
    {
        parent::setUpFilledContainers($params);
        
        [, $this->missingElement, ] = $params;
        
        $this->missingElement[0] = "\xff";
    }
    
    public function tearDown(array $params): void
    {
        \assert(false === $this->exists);
    }
    
    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchCheckingExistenceOfMissingElementWithArray(array $params): void
    {
        $this->exists = \in_array($this->missingElement, $this->array, true);
    }
    
    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchCheckingExistenceOfMissingElementWithBytemap(array $params): void
    {
        foreach ($this->bytemap->find([$this->missingElement], true, 1) as $element) {
            $this->exists = true;
        }
    }
    
    /**
     * @ParamProviders({"providePairsAndDsDeque"})
     */
    public function benchCheckingExistenceOfMissingElementWithDsDeque(array $params): void
    {
        $this->exists = $this->dsDeque->contains($this->missingElement);
    }
    
    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchCheckingExistenceOfMissingElementWithDsVector(array $params): void
    {
        $this->exists = $this->dsVector->contains($this->missingElement);
    }
    
    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchCheckingExistenceOfMissingElementWithSplFixedArray(array $params): void
    {
        foreach ($this->splFixedArray as $element) {
            if ($this->missingElement === $element) {
                $this->exists = true;
                break;
            }
        }
    }
}

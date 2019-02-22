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
    
    public function setUpFilledContainers(array $params): void
    {
        parent::setUpFilledContainers($params);
        
        [, $inserted, ] = $params;
        
        for ($i = 0; $i < self::MISSING_ELEMENT_COUNT; ++$i) {
            $this->missingElements[] = \chr(0xff - $i).\substr($inserted, 1);
        }
    }
    
    public function tearDown(array $params): void
    {
        \assert(false === $this->exists);
    }
    
    /**
     * @ParamProviders({"providePairsAndArray"})
     */
    public function benchCheckingExistenceOfMissingElementsWithArray(array $params): void
    {
        $this->exists = !\array_diff($this->array, $this->missingElements);
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
        $this->exists = !\array_diff($this->dsDeque->toArray(), $this->missingElements);
    }
    
    /**
     * @ParamProviders({"providePairsAndDsVector"})
     */
    public function benchCheckingExistenceOfMissingElementsWithDsVector(array $params): void
    {
        $this->exists = !\array_diff($this->dsVector->toArray(), $this->missingElements);
    }
    
    /**
     * @ParamProviders({"providePairsAndSplFixedArray"})
     */
    public function benchCheckingExistenceOfMissingElementsWithSplFixedArray(array $params): void
    {
        $this->exists = !\array_diff($this->splFixedArray->toArray(), $this->missingElements);
    }
}

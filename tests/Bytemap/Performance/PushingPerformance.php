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
 * @Groups({"Time"})
 * @ParamProviders({"providePairs"})
 * @Revs(100000)
 *
 * @internal
 */
final class PushingPerformance extends AbstractTestOfPerformance
{
    private /* int */ $lastIndex = 0;

    public function benchPushingWithArray(array $params): void
    {
        [, $inserted] = $params;
        $this->array[] = $inserted;
    }

    public function benchPushingWithBytemap(array $params): void
    {
        [, $inserted] = $params;
        $this->bytemap[] = $inserted;
    }

    public function benchPushingWithDsDeque(array $params): void
    {
        [, $inserted] = $params;
        $this->dsDeque->push($inserted);
    }

    public function benchPushingWithDsVector(array $params): void
    {
        [, $inserted] = $params;
        $this->dsVector->push($inserted);
    }

    public function benchPushingWithSplFixedArray(array $params): void
    {
        [, $inserted] = $params;
        $this->splFixedArray->setSize($this->lastIndex + 1);
        $this->splFixedArray[$this->lastIndex++] = $inserted;
    }
}

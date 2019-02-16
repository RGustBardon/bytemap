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
 * @ParamProviders({"providePairs"})
 * @Iterations(5)
 *
 * @internal
 */
final class JsonStreamingPerformance extends AbstractTestOfPerformance
{
    protected const CONTAINER_ELEMENT_COUNT = 500000;

    private $stream;

    public function tearDown(array $params): void
    {
        \fclose($this->stream);
    }

    public function setUpFilledContainers(array $params): void
    {
        parent::setUpFilledContainers($params);

        \assert(\class_exists('\\JsonStreamingParser\\Parser'));
        $this->stream = \fopen('php://temp/maxmemory:0', 'r+b');
        \assert(\is_resource($this->stream));
    }

    /**
     * @ParamProviders({"providePairsAndBytemap"})
     */
    public function benchJsonStreaming(array $params): void
    {
        $this->bytemap->streamJson($this->stream);
    }
}

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

use Bytemap\BytemapInterface;

require_once __DIR__.'/tests/bootstrap.php';

/*
 * Benchmark a particular implementation of a bytemap.
 *
 * This file is meant to be run by `benchmark.sh`.
 *
 * @author Robert Gust-Bardon
 *
 * @internal
 */
new class($GLOBALS['argv'][1], $GLOBALS['argv'][2] ?? null) {
    private const BENCHMARK_MEMORY = 'Memory';
    private const BENCHMARK_NATIVE_SERIALIZE = 'NativeSerialize';
    private const BENCHMARK_SEARCH_FIND_SINGLE_NONE_FORWARD = 'SearchFindSingleNoneForward';
    private const BENCHMARK_SEARCH_FIND_SINGLE_NONE_BACKWARD = 'SearchFindSingleNoneBackward';

    private const JSON_FLAGS =
        \JSON_NUMERIC_CHECK | \JSON_PRESERVE_ZERO_FRACTION | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES;

    private $impl;
    private $runtimeId;
    private $statusHandle;

    private $initialMemUsage;
    private $initialMemPeak;
    private $initialTimestamp;

    private $snapshots = [];

    public function __construct(string $impl, ?string $benchmark)
    {
        \error_reporting(\E_ALL);
        \ini_set('assert.exception', '1');

        if ('--list-benchmarks' === $impl || null === $benchmark) {
            echo implode(\PHP_EOL, self::getBenchmarkNames()), PHP_EOL;
            exit(0);
        }

        $this->impl = $impl;
        $dsStatus = \extension_loaded('ds') ? 'extension' : 'polyfill';
        $this->runtimeId = \sprintf('%s %s (%s, php-ds/%s)', $benchmark, $impl, \PHP_VERSION, $dsStatus);
        $this->statusHandle = \fopen('/proc/'.\getmypid().'/status', 'r');
        \assert(\is_resource($this->statusHandle), $this->runtimeId);

        $this->instantiate("\x00");
        $this->resetMeasurements();
        $this->benchmark($benchmark);
    }

    public function __destruct()
    {
        if (\is_resource($this->statusHandle)) {
            \fclose($this->statusHandle);
        }
        if ($this->snapshots) {
            echo \json_encode($this->snapshots, self::JSON_FLAGS), \PHP_EOL;
        }
    }

    public function benchmark(string $benchmark): void
    {
        switch ($benchmark) {
            case self::BENCHMARK_MEMORY:
                $this->takeSnapshot('Initial');
                $bytemap = $this->instantiate("\x00");
                $i = 0;
                foreach (range(100000, 1000000, 100000) as $itemCount) {
                    for (; $i < $itemCount; ++$i) {
                        $bytemap[] = "\x02";
                    }
                    $this->takeSnapshot(\sprintf('%dk items', $itemCount / 1000));
                }
                \assert("\x02" === $bytemap[42], $this->runtimeId);
                \assert("\x02" === $bytemap[1000000 - 1], $this->runtimeId);
                $bytemap = null;
                $this->takeSnapshot('AfterUnset');

                break;
            case self::BENCHMARK_NATIVE_SERIALIZE:
                $this->takeSnapshot('Initial');
                $bytemap = $this->instantiate("\x00");
                $i = 0;
                foreach (range(100000, 1000000, 100000) as $itemCount) {
                    for (; $i < $itemCount; ++$i) {
                        $bytemap[] = "\x02";
                    }
                    $this->takeSnapshot(\sprintf('After resizing to %dk', $itemCount / 1000));
                    $length = \strlen(\serialize($bytemap));
                    $this->takeSnapshot(\sprintf('After serializing %dk items (%d bytes)', $itemCount / 1000, $length));
                }
                \assert("\x02" === $bytemap[42], $this->runtimeId);
                \assert("\x02" === $bytemap[1000000 - 1], $this->runtimeId);
                $bytemap = null;
                $this->takeSnapshot('AfterUnset');

                break;
            case self::BENCHMARK_SEARCH_FIND_SINGLE_NONE_FORWARD:
                $bytemap = $this->createCyclicBytemap('0', '9');

                $result = $bytemap->find('a', true, \PHP_INT_MAX);
                $this->takeSnapshot('After attempting to find a going forward');
                \assert(empty(\iterator_to_array($result)), $this->runtimeId);

                $result = $bytemap->find(\range('a', 'z'), true, \PHP_INT_MAX);
                $this->takeSnapshot('After attempting to find a-z going forward');
                \assert(empty(\iterator_to_array($result)), $this->runtimeId);

                break;
            case self::BENCHMARK_SEARCH_FIND_SINGLE_NONE_BACKWARD:
                $bytemap = $this->createCyclicBytemap('0', '9');

                $result = $bytemap->find(['a'], true, -\PHP_INT_MAX);
                $this->takeSnapshot('After attempting to find a going backward');
                \assert(empty(\iterator_to_array($result)), $this->runtimeId);

                $result = $bytemap->find(\range('a', 'z'), true, -\PHP_INT_MAX);
                $this->takeSnapshot('After attempting to find a-z going backward');
                \assert(empty(\iterator_to_array($result)), $this->runtimeId);

                break;
            default:
                throw new \UnexpectedValueException('Invalid benchmark id.');
        }
    }

    private function instantiate(...$args): BytemapInterface
    {
        return new $this->impl(...$args);
    }

    private function createCyclicBytemap(string $firstItem, string $lastItem, int $size = 100000): BytemapInterface
    {
        $items = \range($firstItem, $lastItem);
        $itemCount = \count($items);
        $bytemap = $this->instantiate("\x00");
        for ($i = 0; $i < $size; ++$i) {
            $bytemap[] = (string) $items[$i % $itemCount];
        }
        $format = 'After creating a cyclic bytemap of %s-%s (of size %dk)';
        $this->takeSnapshot(\sprintf($format, $firstItem, $lastItem, \count($bytemap) / 1000));

        return $bytemap;
    }

    private function resetMeasurements(): void
    {
        $this->initialMemUsage = \memory_get_usage(true);
        $this->initialMemPeak = \memory_get_peak_usage(true);
        $this->initialTimestamp = \microtime(true);
    }

    private function takeSnapshot(string $name): void
    {
        $microtime = \round(\microtime(true) - $this->initialTimestamp, 6);
        \assert(!isset($this->snapshots[$name]), $this->runtimeId);
        $this->snapshots[$name] = [
            'PhpRuntime' => $this->runtimeId,
            'PhpSnapshot' => $name,
            'PhpTime' => $microtime,
            'PhpMem' => \memory_get_usage(true) - $this->initialMemUsage,
            'PhpPeak' => \memory_get_peak_usage(true) - $this->initialMemPeak,
        ];
        $rewound = \rewind($this->statusHandle);
        \assert($rewound, $this->runtimeId);
        while (true) {
            $buffer = \fgets($this->statusHandle);
            if (false === $buffer) {
                break;
            }
            [$key, $value] = \explode(':', \trim($buffer), 2);
            $value = \preg_replace('~\\s+~', ' ', \trim($value));
            if (\preg_match('~^[0-9]+ kB$~', $value)) {
                $value = 1024 * (int) \substr($value, 0, -3);
            }
            $this->snapshots[$name][$key] = $value;
        }
    }

    private static function getBenchmarkNames(): array
    {
        $names = [];
        $class = new \ReflectionClass(__CLASS__);
        foreach ($class->getConstants() as $key => $value) {
            if ('BENCHMARK_' === \substr($key, 0, 10)) {
                $names[] = $value;
            }
        }

        return $names;
    }
};

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
    private const BENCHMARK_SEARCH_FIND_NONE = 'SearchFindNone';
    private const BENCHMARK_SEARCH_FIND_SOME = 'SearchFindSome';
    private const BENCHMARK_SEARCH_FIND_ALL = 'SearchFindAll';
    private const BENCHMARK_SEARCH_GREP_NONE = 'SearchGrepNone';
    private const BENCHMARK_SEARCH_GREP_SOME = 'SearchGrepSome';
    private const BENCHMARK_SEARCH_GREP_ALL = 'SearchGrepAll';

    private const DEFAULT_BYTEMAP_ITEM_COUNT = 100000;

    private const JSON_FLAGS =
        \JSON_NUMERIC_CHECK | \JSON_PRESERVE_ZERO_FRACTION | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES;

    private $impl;
    private $runtimeId;
    private $statusHandle;

    private $initialMemUsage;
    private $initialMemPeak;
    private $initialTimestamp;
    private $totalTime;

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
                $this->takeSnapshot('Initial', false);
                $bytemap = $this->instantiate("\x00");
                $i = 0;
                foreach (range(100000, 1000000, 100000) as $itemCount) {
                    for (; $i < $itemCount; ++$i) {
                        $bytemap[] = "\x02";
                    }
                    $this->takeSnapshot(\sprintf('%dk items', $itemCount / 1000), true);
                }
                \assert("\x02" === $bytemap[42], $this->runtimeId);
                \assert("\x02" === $bytemap[1000000 - 1], $this->runtimeId);
                $bytemap = null;
                $this->takeSnapshot('AfterUnset', true);

                break;
            case self::BENCHMARK_NATIVE_SERIALIZE:
                $this->takeSnapshot('Initial', false);
                $bytemap = $this->instantiate("\x00");
                $i = 0;
                foreach (range(100000, 1000000, 100000) as $itemCount) {
                    for (; $i < $itemCount; ++$i) {
                        $bytemap[] = "\x02";
                    }
                    $this->takeSnapshot(\sprintf('After resizing to %dk', $itemCount / 1000), false);
                    $length = \strlen(\serialize($bytemap));
                    $this->takeSnapshot(\sprintf('After serializing %dk items (%d bytes)', $itemCount / 1000, $length), true);
                }
                \assert("\x02" === $bytemap[42], $this->runtimeId);
                \assert("\x02" === $bytemap[1000000 - 1], $this->runtimeId);

                break;
            case self::BENCHMARK_SEARCH_FIND_NONE:
                foreach ([
                    ['0', '9', [['a'], ['a', 'z']]],
                    ['10', '99', [['aa'], ['aa', 'zz']]],
                ] as [$first, $last, $needles]) {
                    foreach ($needles as $needle) {
                        foreach ([true, false] as $forward) {
                            $itemCount = $this->benchmarkSearchFind($first, $last, $forward, $needle[0], $needle[1] ?? null);
                            \assert(0 === $itemCount, $this->runtimeId);
                        }
                    }
                }

                break;
            case self::BENCHMARK_SEARCH_FIND_SOME:
                foreach ([
                    ['0', '9', [[['4'], 10000], [['4', '7'], 40000]]],
                    ['10', '99', [[['40'], 1111], [['40', '70'], 34441]]],
                ] as [$first, $last, $needlesAndItemCounts]) {
                    foreach ($needlesAndItemCounts as [$needle, $expectedItemCount]) {
                        foreach ([true, false] as $forward) {
                            $itemCount = $this->benchmarkSearchFind($first, $last, $forward, $needle[0], $needle[1] ?? null);
                            \assert($expectedItemCount === $itemCount, $this->runtimeId);
                        }
                    }
                }

                break;
            case self::BENCHMARK_SEARCH_FIND_ALL:
                foreach ([
                    ['4', '7', '0', '9'],
                    ['40', '70', '10', '99'],
                ] as [$firstItem, $lastItem, $firstNeedle, $lastNeedle]) {
                    foreach ([true, false] as $forward) {
                        $itemCount = $this->benchmarkSearchFind($firstItem, $lastItem, $forward, $firstNeedle, $lastNeedle);
                        \assert(self::DEFAULT_BYTEMAP_ITEM_COUNT === $itemCount, $this->runtimeId);
                    }
                }

                break;
            case self::BENCHMARK_SEARCH_GREP_NONE:
                foreach ([
                    ['0', '9', ['[a-z]']],
                    ['10', '99', ['^a', 'z$', '[a-z]{2}']],
                ] as [$first, $last, $regexes]) {
                    foreach ($regexes as $regex) {
                        foreach ([true, false] as $forward) {
                            $itemCount = $this->benchmarkSearchGrep($first, $last, $forward, $regex);
                            \assert(0 === $itemCount, $this->runtimeId);
                        }
                    }
                }

                break;
            case self::BENCHMARK_SEARCH_GREP_SOME:
                foreach ([
                    ['0', '9', ['~[24-6]~' => 40000]],
                    ['10', '99', ['~^1~' => 11120, '~0$~' => 10000, '~1~' => 20008, '~[1-3][4-6]~' => 10002]],
                ] as [$first, $last, $regexes]) {
                    foreach ($regexes as $regex => $expectedItemCount) {
                        foreach ([true, false] as $forward) {
                            $itemCount = $this->benchmarkSearchGrep($first, $last, $forward, $regex);
                            \assert($expectedItemCount === $itemCount, $this->runtimeId);
                        }
                    }
                }

                break;
            case self::BENCHMARK_SEARCH_GREP_ALL:
                foreach ([
                    ['4', '7', ['~~', '~.~', '~[0-9]~', '~[^a-z]~']],
                    ['40', '70', ['~^[3-8]~', '~[^a-z]$~', '~(?<![b-y])[^a-z]~', '~[3-8][^a-z]~']],
                ] as [$first, $last, $regexes]) {
                    foreach ($regexes as $regex) {
                        foreach ([true, false] as $forward) {
                            $itemCount = $this->benchmarkSearchGrep($first, $last, $forward, $regex);
                            \assert(self::DEFAULT_BYTEMAP_ITEM_COUNT === $itemCount, $this->runtimeId);
                        }
                    }
                }

                break;
            default:
                throw new \UnexpectedValueException('Invalid benchmark id.');
        }
    }

    private function instantiate(...$args): BytemapInterface
    {
        return new $this->impl(...$args);
    }

    private function benchmarkSearchFind(
        string $firstCyclicItem,
        string $lastCyclicItem,
        bool $forward,
        string $firstNeedle,
        ?string $lastNeedle = null
    ): int {
        $bytemap = $this->createCyclicBytemap($firstCyclicItem, $lastCyclicItem);
        if (null === $lastNeedle) {
            $items = [$firstNeedle];
            $needle = $firstNeedle;
        } else {
            $items = \array_map('strval', \range($firstNeedle, $lastNeedle));
            $needle = \sprintf('%s-%s', $firstNeedle, $lastNeedle);
        }
        $direction = $forward ? 'forward' : 'backward';

        $result = $bytemap->find($items, true, $forward ? \PHP_INT_MAX : -\PHP_INT_MAX);
        $itemCount = 0;
        foreach ($result as $key => $value) {
            ++$itemCount;
        }
        $this->takeSnapshot(\sprintf('After attempting to find %s going %s', $needle, $direction), true);

        return $itemCount;
    }

    private function benchmarkSearchGrep(string $firstCyclicItem, string $lastCyclicItem, bool $forward, string $regex): int
    {
        $bytemap = $this->createCyclicBytemap($firstCyclicItem, $lastCyclicItem);
        $direction = $forward ? 'forward' : 'backward';

        $result = $bytemap->grep($regex, true, $forward ? \PHP_INT_MAX : -\PHP_INT_MAX);
        $itemCount = 0;
        foreach ($result as $key => $value) {
            ++$itemCount;
        }
        $this->takeSnapshot(\sprintf('After attempting to grep %s going %s', $regex, $direction), true);

        return $itemCount;
    }

    private function createCyclicBytemap(
        string $firstItem,
        string $lastItem,
        int $size = self::DEFAULT_BYTEMAP_ITEM_COUNT
    ): BytemapInterface {
        $items = \array_map('strval', \range($firstItem, $lastItem));
        $itemCount = \count($items);
        $bytemap = $this->instantiate($items[0]);
        for ($i = 0; $i < $size; ++$i) {
            $bytemap[] = $items[$i % $itemCount];
        }
        $format = 'After creating a cyclic bytemap of %s-%s (of size %dk)';
        $this->takeSnapshot(\sprintf($format, $firstItem, $lastItem, \count($bytemap) / 1000), false);

        return $bytemap;
    }

    private function resetMeasurements(): void
    {
        $this->initialMemUsage = \memory_get_usage(true);
        $this->initialMemPeak = \memory_get_peak_usage(true);
        $this->initialTimestamp = \microtime(true);
        $this->totalTime = .0;
    }

    private function takeSnapshot(string $name, bool $isRelevant): void
    {
        $currentTime = \microtime(true);
        $microtime = \round($currentTime - $this->initialTimestamp, 6);
        $lastSnapshot = \end($this->snapshots);
        $totalTime = $lastSnapshot['PhpTotalRelevantTime'] ?: .0;
        if ($isRelevant && isset($lastSnapshot['PhpTime'])) {
            $totalTime += $microtime - $lastSnapshot['PhpTime'];
        }
        $snapshot = [
            'PhpRuntime' => $this->runtimeId,
            'PhpSnapshot' => $name,
            'PhpIsRelevantStep' => $isRelevant,
            'PhpTime' => $microtime,
            'PhpTotalRelevantTime' => \round($totalTime, 6),
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
            $snapshot[$name][$key] = $value;
        }
        $this->snapshots[] = $snapshot;
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

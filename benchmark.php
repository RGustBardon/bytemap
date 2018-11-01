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
    private const BENCHMARK_JSON_STREAM = 'JsonStream';
    private const BENCHMARK_MEMORY = 'Memory';
    private const BENCHMARK_NATIVE_EXPAND = 'NativeExpand';
    private const BENCHMARK_NATIVE_FOREACH = 'NativeForeach';
    private const BENCHMARK_NATIVE_JSON_SERIALIZE = 'NativeJsonSerialize';
    private const BENCHMARK_NATIVE_OVERWRITING = 'NativeOverwriting';
    private const BENCHMARK_NATIVE_PUSH = 'NativePush';
    private const BENCHMARK_NATIVE_RANDOM_ACCESS = 'NativeRandomAccess';
    private const BENCHMARK_NATIVE_SERIALIZE = 'NativeSerialize';
    private const BENCHMARK_NATIVE_UNSET_TAIL = 'NativeUnsetTail';
    private const BENCHMARK_MUTATION_INSERTION_HEAD = 'MutationInsertionHead';
    private const BENCHMARK_MUTATION_INSERTION_TAIL = 'MutationInsertionTail';
    private const BENCHMARK_MUTATION_DELETION_HEAD = 'MutationDeletionHead';
    private const BENCHMARK_MUTATION_DELETION_TAIL = 'MutationDeletionTail';
    private const BENCHMARK_SEARCH_FIND_NONE = 'SearchFindNone';
    private const BENCHMARK_SEARCH_FIND_SOME = 'SearchFindSome';
    private const BENCHMARK_SEARCH_FIND_ALL = 'SearchFindAll';
    private const BENCHMARK_SEARCH_GREP_NONE = 'SearchGrepNone';
    private const BENCHMARK_SEARCH_GREP_SOME = 'SearchGrepSome';
    private const BENCHMARK_SEARCH_GREP_ALL = 'SearchGrepAll';

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
        \mt_srand(0);

        if ('--list-benchmarks' === $impl || null === $benchmark) {
            echo \implode(\PHP_EOL, self::getBenchmarkNames()), \PHP_EOL;
            exit(0);
        }

        $this->impl = $impl;
        $dsStatus = \extension_loaded('ds') ? 'extension' : 'polyfill';
        $this->runtimeId = \sprintf('%s %s (%s, php-ds/%s)', $benchmark, $impl, \PHP_VERSION, $dsStatus);
        $this->statusHandle = \fopen('/proc/'.\getmypid().'/status', 'rb');
        \assert(\is_resource($this->statusHandle), $this->runtimeId);

        $this->instantiate("\x00");
        $this->resetMeasurements();
        $this->takeSnapshot('Initial', false);
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
            case self::BENCHMARK_JSON_STREAM:
                \assert(\class_exists('\\JsonStreamingParser\\Parser'));
                $stream = \fopen('php://temp/maxmemory:0', 'r+b');
                \assert(\is_resource($stream));
                $bytemap = $this->instantiate("\x00\x00\x00\x00");
                for ($i = 'aaaa'; 'kaaa' !== $i; ++$i) {
                    $bytemap[] = $i;
                }
                $itemCount = \count($bytemap);
                $this->takeSnapshot(\sprintf('After creating a bytemap with %d items', $itemCount), false);
                $bytemap->streamJson($stream);
                $this->takeSnapshot('After streaming JSON', true);
                unset($bytemap);
                \rewind($stream);
                $bytemap = $this->instantiate("\x00")->parseJsonStream($stream, "\x00\x00\x00\x00");
                $this->takeSnapshot('After parsing the stream', true);
                \fclose($stream);
                \assert('aaab' === $bytemap[1], $this->runtimeId);
                \assert('jzzy' === $bytemap[$itemCount - 2], $this->runtimeId);

                break;
            case self::BENCHMARK_MEMORY:
                $bytemap = $this->instantiate("\x00");
                $i = 0;
                foreach (\range(100000, 1000000, 100000) as $itemCount) {
                    for (; $i < $itemCount; ++$i) {
                        $bytemap[] = "\x02";
                    }
                    $this->takeSnapshot(\sprintf('%dk items', $itemCount / 1000), true);
                }
                \assert("\x02" === $bytemap[42], $this->runtimeId);
                \assert("\x02" === $bytemap[1000000 - 1], $this->runtimeId);
                $bytemap = null;
                $this->takeSnapshot('After setting the bytemap to NULL', true);

                break;
            case self::BENCHMARK_NATIVE_EXPAND:
                $iterations = 30000;

                $bytemap = $this->instantiate("\x00");
                $itemCount = 0;
                for ($i = 0; $i < $iterations; ++$i) {
                    $index = $itemCount + \mt_rand(1, 100);
                    $bytemap[$index] = "\x01";
                    $itemCount = $index + 1;
                }
                $this->takeSnapshot(\sprintf('After expanding a single-byte bytemap %d times', $iterations), true);
                unset($bytemap);

                \mt_srand(0);
                $bytemap = $this->instantiate("\x00\x00\x00\x00");
                $itemCount = 0;
                for ($i = 0; $i < $iterations; ++$i) {
                    $index = $itemCount + \mt_rand(1, 100);
                    $bytemap[$index] = "\x01\x02\x03\x04";
                    $itemCount = $index + 1;
                }
                $this->takeSnapshot(\sprintf('After expanding a four-byte bytemap %d times', $iterations), true);

                break;
            case self::BENCHMARK_NATIVE_FOREACH:
                $bytemap = $this->createCyclicBytemap('0', '9', 26 ** 4);
                foreach ($bytemap as $item) {
                }
                $this->takeSnapshot('After iterating over the bytemap with 1 byte per item', true);
                unset($bytemap);

                $bytemap = $this->createCyclicBytemap('aaaa', 'zzzz');
                foreach ($bytemap as $item) {
                }
                $this->takeSnapshot('After iterating over the bytemap with 4 bytes per item', true);

                break;
            case self::BENCHMARK_NATIVE_JSON_SERIALIZE:
                \json_encode($this->createCyclicBytemap('aaaa', 'zzzz'));
                $this->takeSnapshot('After serializing to JSON natively', true);
                \assert(\JSON_ERROR_NONE === \json_last_error());

                break;
            case self::BENCHMARK_NATIVE_OVERWRITING:
                $iterations = 1000000;

                $bytemap = $this->createCyclicBytemap('0', '9', 26 ** 4);
                $itemCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap[\mt_rand(0, $itemCount - 1)] = 'a';
                }
                $this->takeSnapshot(\sprintf('After updating %d items (1 byte each) in pseudorandom order', $iterations), true);
                unset($bytemap);

                $bytemap = $this->createCyclicBytemap('aaaa', 'zzzz');
                $itemCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap[\mt_rand(0, $itemCount - 1)] = 'abcd';
                }
                $this->takeSnapshot(\sprintf('After updating %d items (4 bytes each) in pseudorandom order', $iterations), true);

                break;
            case self::BENCHMARK_NATIVE_PUSH:
                $bytemap = $this->instantiate("\x00");
                for ($i = 0; $i < 2 * 26 ** 4; ++$i) {
                    $bytemap[] = (string) ($i % 10);
                }
                $this->takeSnapshot(\sprintf('After pushing %d items (1 byte each) one by one', \count($bytemap)), true);
                unset($bytemap);

                $bytemap = $this->instantiate("\x00\x00\x00\x00");
                for ($j = 0; $j < 2; ++$j) {
                    for ($i = 'aaaa'; 'aaaaa' !== $i; ++$i) {
                        $bytemap[] = $i;
                    }
                }
                $this->takeSnapshot(\sprintf('After pushing %d items (4 bytes each)', \count($bytemap)), true);

                break;
            case self::BENCHMARK_NATIVE_RANDOM_ACCESS:
                $iterations = 1000000;

                $bytemap = $this->createCyclicBytemap('0', '9', 26 ** 4);
                $itemCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap[\mt_rand(0, $itemCount - 1)];
                }
                $this->takeSnapshot(\sprintf('After retrieving %d items (1 byte each) in pseudorandom order', $iterations), true);
                unset($bytemap);

                $bytemap = $this->createCyclicBytemap('aaaa', 'zzzz');
                $itemCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap[\mt_rand(0, $itemCount - 1)];
                }
                $this->takeSnapshot(\sprintf('After retrieving %d items (4 bytes each) in pseudorandom order', $iterations), true);

                break;
            case self::BENCHMARK_NATIVE_SERIALIZE:
                $bytemap = $this->instantiate("\x00");
                $i = 0;
                foreach (\range(100000, 800000, 100000) as $itemCount) {
                    for (; $i < $itemCount; ++$i) {
                        $bytemap[] = "\x02";
                    }
                    $this->takeSnapshot(\sprintf('After resizing to %dk', $itemCount / 1000), false);
                    $serializedBytemap = \serialize($bytemap);
                    $length = \strlen($serializedBytemap);
                    $this->takeSnapshot(\sprintf('After serializing %dk items (%d bytes)', $itemCount / 1000, $length), true);
                    unset($bytemap);
                    $bytemap = \unserialize($serializedBytemap, ['allowed_classes' => [$this->impl]]);
                    $this->takeSnapshot(\sprintf('After unserializing %dk items', $itemCount / 1000), true);
                    \assert("\x02" === $bytemap[42], $this->runtimeId);
                    \assert("\x02" === $bytemap[$itemCount - 1], $this->runtimeId);
                    \assert(\count($bytemap) === $itemCount, $this->runtimeId);
                }
                unset($bytemap);

                break;
            case self::BENCHMARK_NATIVE_UNSET_TAIL:
                $iterations = 30000;

                $bytemap = $this->createCyclicBytemap('0', '9', $iterations);
                $itemCount = \count($bytemap);
                for ($i = $itemCount - 1; $i >= 0; --$i) {
                    unset($bytemap[$i]);
                }
                $this->takeSnapshot('After unsetting the tails (1 byte each) one by one', true);
                \assert(0 === \count($bytemap), $this->runtimeId);
                unset($bytemap);

                $bytemap = $this->createCyclicBytemap('aaaa', 'zzzz', $iterations);
                $itemCount = \count($bytemap);
                for ($i = $itemCount - 1; $i >= 0; --$i) {
                    unset($bytemap[$i]);
                }
                $this->takeSnapshot('After unsetting the tails (4 bytes each) one by one', true);
                \assert(0 === \count($bytemap), $this->runtimeId);

                break;
            case self::BENCHMARK_MUTATION_INSERTION_HEAD:
                $bytemap = $this->instantiate("\x00");
                for ($i = 0; $i < 200; ++$i) {
                    $bytemap->insert(\array_fill(0, \mt_rand(1, 1000), "\x01"), 0);
                }
                $this->takeSnapshot(\sprintf('After unshifting %d items (1 byte each) in random batches', \count($bytemap)), true);
                unset($bytemap);

                \mt_srand(0);
                $bytemap = $this->instantiate("\x00\x00\x00\x00");
                for ($i = 0; $i < 200; ++$i) {
                    $bytemap->insert(\array_fill(0, \mt_rand(1, 1000), "\x01\x02\x03\x04"), 0);
                }
                $this->takeSnapshot(\sprintf('After unshifting %d items (4 bytes each) in random batches', \count($bytemap)), true);

                break;
            case self::BENCHMARK_MUTATION_INSERTION_TAIL:
                $bytemap = $this->instantiate("\x00");
                for ($i = 0; $i < 2000; ++$i) {
                    $bytemap->insert(\array_fill(0, \mt_rand(1, 1000), "\x01"));
                }
                $this->takeSnapshot(\sprintf('After pushing %d items (1 byte each) in random batches', \count($bytemap)), true);
                unset($bytemap);

                \mt_srand(0);
                $bytemap = $this->instantiate("\x00\x00\x00\x00");
                for ($i = 0; $i < 2000; ++$i) {
                    $bytemap->insert(\array_fill(0, \mt_rand(1, 1000), "\x01\x02\x03\x04"));
                }
                $this->takeSnapshot(\sprintf('After pushing %d items (4 bytes each) in random batches', \count($bytemap)), true);

                break;
            case self::BENCHMARK_MUTATION_DELETION_HEAD:
                $iterations = 100;

                $bytemap = $this->createCyclicBytemap('0', '9', 3 * 26 ** 3);
                $itemCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap->delete(0, \mt_rand(1, 1000));
                }
                $itemCount -= \count($bytemap);
                $this->takeSnapshot(\sprintf('After deleting the first %d items (1 byte each) in random batches', $itemCount), true);
                unset($bytemap);

                \mt_srand(0);
                $bytemap = $this->createCyclicBytemap('aaaa', 'czzz');
                $itemCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap->delete(0, \mt_rand(1, 1000));
                }
                $itemCount -= \count($bytemap);
                $this->takeSnapshot(\sprintf('After deleting the first %d items (4 bytes each) in random batches', $itemCount), true);

                break;
            case self::BENCHMARK_MUTATION_DELETION_TAIL:
                $iterations = 100;

                $bytemap = $this->createCyclicBytemap('0', '9', 3 * 26 ** 3);
                $itemCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap->delete(-\mt_rand(1, 1000));
                }
                $itemCount -= \count($bytemap);
                $this->takeSnapshot(\sprintf('After deleting the last %d items (1 byte each) in random batches', $itemCount), true);
                unset($bytemap);

                \mt_srand(0);
                $bytemap = $this->createCyclicBytemap('aaaa', 'czzz');
                $itemCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap->delete(-\mt_rand(1, 1000));
                }
                $itemCount -= \count($bytemap);
                $this->takeSnapshot(\sprintf('After deleting the last %d items (4 bytes each) in random batches', $itemCount), true);

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
                    ['0', '9', [[['4'], 20000], [['4', '7'], 80000]]],
                    ['10', '99', [[['40'], 2222], [['40', '70'], 68882]]],
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
                        \assert(200000 === $itemCount, $this->runtimeId);
                    }
                }

                break;
            case self::BENCHMARK_SEARCH_GREP_NONE:
                foreach ([
                    ['0', '9', ['~[a-z]~']],
                    ['10', '99', ['~^a~', '~z$~', '~[a-z]{2}~']],
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
                    ['0', '9', ['~[24-6]~' => 80000]],
                    ['10', '99', ['~^1~' => 22230, '~0$~' => 20000, '~1~' => 40007, '~[1-3][4-6]~' => 20004]],
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
                            \assert(200000 === $itemCount, $this->runtimeId);
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
        $bytemap = $this->createCyclicBytemap($firstCyclicItem, $lastCyclicItem, 200000);
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
        $bytemap = $this->createCyclicBytemap($firstCyclicItem, $lastCyclicItem, 200000);
        $direction = $forward ? 'forward' : 'backward';

        $result = $bytemap->grep([$regex], true, $forward ? \PHP_INT_MAX : -\PHP_INT_MAX);
        $itemCount = 0;
        foreach ($result as $key => $value) {
            ++$itemCount;
        }
        $this->takeSnapshot(\sprintf('After attempting to grep %s going %s', $regex, $direction), true);

        return $itemCount;
    }

    private function createCyclicBytemap(string $firstItem, string $lastItem, ?int $itemCount = null): BytemapInterface
    {
        $bytemap = $this->instantiate($firstItem);

        if (null === $itemCount) {
            for ($lastIteration = false, $item = $firstItem;;) {
                $bytemap[] = $item;
                ++$item;
                $item = (string) $item;
                if ($item === $lastItem) {
                    $lastIteration = true;
                } elseif ($lastIteration) {
                    break;
                }
            }
        } else {
            for ($i = 0, $item = $firstItem; $i < $itemCount; ++$i) {
                $bytemap[] = $item;
                if ($item === $lastItem) {
                    $item = $firstItem;
                } else {
                    ++$item;
                    $item = (string) $item;
                }
            }
        }
        $format = 'After creating a cyclic bytemap of %s-%s with %d items';
        $this->takeSnapshot(\sprintf($format, $firstItem, $lastItem, \count($bytemap)), false);

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
            'ProcessStatus' => [],
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
            $snapshot['ProcessStatus'][$key] = $value;
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

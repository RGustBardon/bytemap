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
                $elementCount = \count($bytemap);
                $this->takeSnapshot(\sprintf('After creating a bytemap with %d elements', $elementCount), false);
                $bytemap->streamJson($stream);
                $this->takeSnapshot('After streaming JSON', true);
                unset($bytemap);
                \rewind($stream);
                $bytemap = $this->instantiate("\x00")->parseJsonStream($stream, "\x00\x00\x00\x00");
                $this->takeSnapshot('After parsing the stream', true);
                \fclose($stream);
                \assert('aaab' === $bytemap[1], $this->runtimeId);
                \assert('jzzy' === $bytemap[$elementCount - 2], $this->runtimeId);

                break;
            case self::BENCHMARK_MEMORY:
                $bytemap = $this->instantiate("\x00");
                $i = 0;
                foreach (\range(100000, 1000000, 100000) as $elementCount) {
                    for (; $i < $elementCount; ++$i) {
                        $bytemap[] = "\x02";
                    }
                    $this->takeSnapshot(\sprintf('%dk elements', $elementCount / 1000), true);
                }
                \assert("\x02" === $bytemap[42], $this->runtimeId);
                \assert("\x02" === $bytemap[1000000 - 1], $this->runtimeId);
                $bytemap = null;
                $this->takeSnapshot('After setting the bytemap to NULL', true);

                break;
            case self::BENCHMARK_NATIVE_EXPAND:
                $iterations = 30000;

                $bytemap = $this->instantiate("\x00");
                $elementCount = 0;
                for ($i = 0; $i < $iterations; ++$i) {
                    $index = $elementCount + \mt_rand(1, 100);
                    $bytemap[$index] = "\x01";
                    $elementCount = $index + 1;
                }
                $this->takeSnapshot(\sprintf('After expanding a single-byte bytemap %d times', $iterations), true);
                unset($bytemap);

                \mt_srand(0);
                $bytemap = $this->instantiate("\x00\x00\x00\x00");
                $elementCount = 0;
                for ($i = 0; $i < $iterations; ++$i) {
                    $index = $elementCount + \mt_rand(1, 100);
                    $bytemap[$index] = "\x01\x02\x03\x04";
                    $elementCount = $index + 1;
                }
                $this->takeSnapshot(\sprintf('After expanding a four-byte bytemap %d times', $iterations), true);

                break;
            case self::BENCHMARK_NATIVE_FOREACH:
                $bytemap = $this->createCyclicBytemap('0', '9', 26 ** 4);
                foreach ($bytemap as $element) {
                }
                $this->takeSnapshot('After iterating over the bytemap with 1 byte per element', true);
                unset($bytemap);

                $bytemap = $this->createCyclicBytemap('aaaa', 'zzzz');
                foreach ($bytemap as $element) {
                }
                $this->takeSnapshot('After iterating over the bytemap with 4 bytes per element', true);

                break;
            case self::BENCHMARK_NATIVE_JSON_SERIALIZE:
                \json_encode($this->createCyclicBytemap('aaaa', 'zzzz'));
                $this->takeSnapshot('After serializing to JSON natively', true);
                \assert(\JSON_ERROR_NONE === \json_last_error());

                break;
            case self::BENCHMARK_NATIVE_OVERWRITING:
                $iterations = 1000000;

                $bytemap = $this->createCyclicBytemap('0', '9', 26 ** 4);
                $elementCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap[\mt_rand(0, $elementCount - 1)] = 'a';
                }
                $this->takeSnapshot(\sprintf('After updating %d elements (1 byte each) in pseudorandom order', $iterations), true);
                unset($bytemap);

                $bytemap = $this->createCyclicBytemap('aaaa', 'zzzz');
                $elementCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap[\mt_rand(0, $elementCount - 1)] = 'abcd';
                }
                $this->takeSnapshot(\sprintf('After updating %d elements (4 bytes each) in pseudorandom order', $iterations), true);

                break;
            case self::BENCHMARK_NATIVE_PUSH:
                $bytemap = $this->instantiate("\x00");
                for ($i = 0; $i < 2 * 26 ** 4; ++$i) {
                    $bytemap[] = (string) ($i % 10);
                }
                $this->takeSnapshot(\sprintf('After pushing %d elements (1 byte each) one by one', \count($bytemap)), true);
                unset($bytemap);

                $bytemap = $this->instantiate("\x00\x00\x00\x00");
                for ($j = 0; $j < 2; ++$j) {
                    for ($i = 'aaaa'; 'aaaaa' !== $i; ++$i) {
                        $bytemap[] = $i;
                    }
                }
                $this->takeSnapshot(\sprintf('After pushing %d elements (4 bytes each)', \count($bytemap)), true);

                break;
            case self::BENCHMARK_NATIVE_RANDOM_ACCESS:
                $iterations = 1000000;

                $bytemap = $this->createCyclicBytemap('0', '9', 26 ** 4);
                $elementCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap[\mt_rand(0, $elementCount - 1)];
                }
                $this->takeSnapshot(\sprintf('After retrieving %d elements (1 byte each) in pseudorandom order', $iterations), true);
                unset($bytemap);

                $bytemap = $this->createCyclicBytemap('aaaa', 'zzzz');
                $elementCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap[\mt_rand(0, $elementCount - 1)];
                }
                $this->takeSnapshot(\sprintf('After retrieving %d elements (4 bytes each) in pseudorandom order', $iterations), true);

                break;
            case self::BENCHMARK_NATIVE_SERIALIZE:
                $bytemap = $this->instantiate("\x00");
                $i = 0;
                foreach (\range(100000, 800000, 100000) as $elementCount) {
                    for (; $i < $elementCount; ++$i) {
                        $bytemap[] = "\x02";
                    }
                    $this->takeSnapshot(\sprintf('After resizing to %dk', $elementCount / 1000), false);
                    $serializedBytemap = \serialize($bytemap);
                    $length = \strlen($serializedBytemap);
                    $this->takeSnapshot(\sprintf('After serializing %dk elements (%d bytes)', $elementCount / 1000, $length), true);
                    unset($bytemap);
                    $bytemap = \unserialize($serializedBytemap, ['allowed_classes' => [$this->impl]]);
                    $this->takeSnapshot(\sprintf('After unserializing %dk elements', $elementCount / 1000), true);
                    \assert("\x02" === $bytemap[42], $this->runtimeId);
                    \assert("\x02" === $bytemap[$elementCount - 1], $this->runtimeId);
                    \assert(\count($bytemap) === $elementCount, $this->runtimeId);
                }
                unset($bytemap);

                break;
            case self::BENCHMARK_NATIVE_UNSET_TAIL:
                $iterations = 30000;

                $bytemap = $this->createCyclicBytemap('0', '9', $iterations);
                $elementCount = \count($bytemap);
                for ($i = $elementCount - 1; $i >= 0; --$i) {
                    unset($bytemap[$i]);
                }
                $this->takeSnapshot('After unsetting the tails (1 byte each) one by one', true);
                \assert(0 === \count($bytemap), $this->runtimeId);
                unset($bytemap);

                $bytemap = $this->createCyclicBytemap('aaaa', 'zzzz', $iterations);
                $elementCount = \count($bytemap);
                for ($i = $elementCount - 1; $i >= 0; --$i) {
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
                $this->takeSnapshot(\sprintf('After unshifting %d elements (1 byte each) in random batches', \count($bytemap)), true);
                unset($bytemap);

                \mt_srand(0);
                $bytemap = $this->instantiate("\x00\x00\x00\x00");
                for ($i = 0; $i < 200; ++$i) {
                    $bytemap->insert(\array_fill(0, \mt_rand(1, 1000), "\x01\x02\x03\x04"), 0);
                }
                $this->takeSnapshot(\sprintf('After unshifting %d elements (4 bytes each) in random batches', \count($bytemap)), true);

                break;
            case self::BENCHMARK_MUTATION_INSERTION_TAIL:
                $bytemap = $this->instantiate("\x00");
                for ($i = 0; $i < 2000; ++$i) {
                    $bytemap->insert(\array_fill(0, \mt_rand(1, 1000), "\x01"));
                }
                $this->takeSnapshot(\sprintf('After pushing %d elements (1 byte each) in random batches', \count($bytemap)), true);
                unset($bytemap);

                \mt_srand(0);
                $bytemap = $this->instantiate("\x00\x00\x00\x00");
                for ($i = 0; $i < 2000; ++$i) {
                    $bytemap->insert(\array_fill(0, \mt_rand(1, 1000), "\x01\x02\x03\x04"));
                }
                $this->takeSnapshot(\sprintf('After pushing %d elements (4 bytes each) in random batches', \count($bytemap)), true);

                break;
            case self::BENCHMARK_MUTATION_DELETION_HEAD:
                $iterations = 100;

                $bytemap = $this->createCyclicBytemap('0', '9', 3 * 26 ** 3);
                $elementCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap->delete(0, \mt_rand(1, 1000));
                }
                $elementCount -= \count($bytemap);
                $this->takeSnapshot(\sprintf('After deleting the first %d elements (1 byte each) in random batches', $elementCount), true);
                unset($bytemap);

                \mt_srand(0);
                $bytemap = $this->createCyclicBytemap('aaaa', 'czzz');
                $elementCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap->delete(0, \mt_rand(1, 1000));
                }
                $elementCount -= \count($bytemap);
                $this->takeSnapshot(\sprintf('After deleting the first %d elements (4 bytes each) in random batches', $elementCount), true);

                break;
            case self::BENCHMARK_MUTATION_DELETION_TAIL:
                $iterations = 100;

                $bytemap = $this->createCyclicBytemap('0', '9', 3 * 26 ** 3);
                $elementCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap->delete(-\mt_rand(1, 1000));
                }
                $elementCount -= \count($bytemap);
                $this->takeSnapshot(\sprintf('After deleting the last %d elements (1 byte each) in random batches', $elementCount), true);
                unset($bytemap);

                \mt_srand(0);
                $bytemap = $this->createCyclicBytemap('aaaa', 'czzz');
                $elementCount = \count($bytemap);
                for ($i = 0; $i < $iterations; ++$i) {
                    $bytemap->delete(-\mt_rand(1, 1000));
                }
                $elementCount -= \count($bytemap);
                $this->takeSnapshot(\sprintf('After deleting the last %d elements (4 bytes each) in random batches', $elementCount), true);

                break;
            case self::BENCHMARK_SEARCH_FIND_NONE:
                foreach ([
                    ['0', '9', [['a'], ['a', 'z']]],
                    ['10', '99', [['aa'], ['aa', 'zz']]],
                ] as [$first, $last, $needles]) {
                    foreach ($needles as $needle) {
                        foreach ([true, false] as $forward) {
                            $elementCount = $this->benchmarkSearchFind($first, $last, $forward, $needle[0], $needle[1] ?? null);
                            \assert(0 === $elementCount, $this->runtimeId);
                        }
                    }
                }

                break;
            case self::BENCHMARK_SEARCH_FIND_SOME:
                foreach ([
                    ['0', '9', [[['4'], 20000], [['4', '7'], 80000]]],
                    ['10', '99', [[['40'], 2222], [['40', '70'], 68882]]],
                ] as [$first, $last, $needlesAndElementCounts]) {
                    foreach ($needlesAndElementCounts as [$needle, $expectedElementCount]) {
                        foreach ([true, false] as $forward) {
                            $elementCount = $this->benchmarkSearchFind($first, $last, $forward, $needle[0], $needle[1] ?? null);
                            \assert($expectedElementCount === $elementCount, $this->runtimeId);
                        }
                    }
                }

                break;
            case self::BENCHMARK_SEARCH_FIND_ALL:
                foreach ([
                    ['4', '7', '0', '9'],
                    ['40', '70', '10', '99'],
                ] as [$firstElement, $lastElement, $firstNeedle, $lastNeedle]) {
                    foreach ([true, false] as $forward) {
                        $elementCount = $this->benchmarkSearchFind($firstElement, $lastElement, $forward, $firstNeedle, $lastNeedle);
                        \assert(200000 === $elementCount, $this->runtimeId);
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
                            $elementCount = $this->benchmarkSearchGrep($first, $last, $forward, $regex);
                            \assert(0 === $elementCount, $this->runtimeId);
                        }
                    }
                }

                break;
            case self::BENCHMARK_SEARCH_GREP_SOME:
                foreach ([
                    ['0', '9', ['~[24-6]~' => 80000]],
                    ['10', '99', ['~^1~' => 22230, '~0$~' => 20000, '~1~' => 40007, '~[1-3][4-6]~' => 20004]],
                ] as [$first, $last, $regexes]) {
                    foreach ($regexes as $regex => $expectedElementCount) {
                        foreach ([true, false] as $forward) {
                            $elementCount = $this->benchmarkSearchGrep($first, $last, $forward, $regex);
                            \assert($expectedElementCount === $elementCount, $this->runtimeId);
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
                            $elementCount = $this->benchmarkSearchGrep($first, $last, $forward, $regex);
                            \assert(200000 === $elementCount, $this->runtimeId);
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
        string $firstCyclicElement,
        string $lastCyclicElement,
        bool $forward,
        string $firstNeedle,
        ?string $lastNeedle = null
    ): int {
        $bytemap = $this->createCyclicBytemap($firstCyclicElement, $lastCyclicElement, 200000);
        if (null === $lastNeedle) {
            $elements = [$firstNeedle];
            $needle = $firstNeedle;
        } else {
            $elements = \array_map('strval', \range($firstNeedle, $lastNeedle));
            $needle = \sprintf('%s-%s', $firstNeedle, $lastNeedle);
        }
        $direction = $forward ? 'forward' : 'backward';

        $result = $bytemap->find($elements, true, $forward ? \PHP_INT_MAX : -\PHP_INT_MAX);
        $elementCount = 0;
        foreach ($result as $element) {
            ++$elementCount;
        }
        $this->takeSnapshot(\sprintf('After attempting to find %s going %s', $needle, $direction), true);

        return $elementCount;
    }

    private function benchmarkSearchGrep(string $firstCyclicElement, string $lastCyclicElement, bool $forward, string $regex): int
    {
        $bytemap = $this->createCyclicBytemap($firstCyclicElement, $lastCyclicElement, 200000);
        $direction = $forward ? 'forward' : 'backward';

        $result = $bytemap->grep([$regex], true, $forward ? \PHP_INT_MAX : -\PHP_INT_MAX);
        $elementCount = 0;
        foreach ($result as $element) {
            ++$elementCount;
        }
        $this->takeSnapshot(\sprintf('After attempting to grep %s going %s', $regex, $direction), true);

        return $elementCount;
    }

    private function createCyclicBytemap(string $firstElement, string $lastElement, ?int $elementCount = null): BytemapInterface
    {
        $bytemap = $this->instantiate($firstElement);

        if (null === $elementCount) {
            for ($lastIteration = false, $element = $firstElement;;) {
                $bytemap[] = $element;
                ++$element;
                $element = (string) $element;
                if ($element === $lastElement) {
                    $lastIteration = true;
                } elseif ($lastIteration) {
                    break;
                }
            }
        } else {
            for ($i = 0, $element = $firstElement; $i < $elementCount; ++$i) {
                $bytemap[] = $element;
                if ($element === $lastElement) {
                    $element = $firstElement;
                } else {
                    ++$element;
                    $element = (string) $element;
                }
            }
        }
        $format = 'After creating a cyclic bytemap of %s-%s with %d elements';
        $this->takeSnapshot(\sprintf($format, $firstElement, $lastElement, \count($bytemap)), false);

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
            if (\is_string($value) && \preg_match('~^[0-9]+ kB$~', $value)) {
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

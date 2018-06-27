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
new class($GLOBALS['argv'][1]) {
    private const JSON_FLAGS =
        \JSON_NUMERIC_CHECK | \JSON_PRESERVE_ZERO_FRACTION | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES;

    private $impl;
    private $runtimeId;
    private $statusHandle;

    private $initialMemUsage;
    private $initialMemPeak;
    private $initialTimestamp;

    private $snapshots = [];

    public function __construct(string $impl)
    {
        \error_reporting(\E_ALL);
        \ini_set('assert.exception', '1');

        $this->impl = $impl;
        $dsStatus = \extension_loaded('ds') ? 'extension' : 'polyfill';
        $this->runtimeId = \sprintf('%s (%s, php-ds/%s)', $impl, \PHP_VERSION, $dsStatus);
        $this->statusHandle = \fopen('/proc/'.\getmypid().'/status', 'r');
        \assert(\is_resource($this->statusHandle), $this->runtimeId);

        $this->instantiate("\x00");
        $this->initialMemUsage = \memory_get_usage(true);
        $this->initialMemPeak = \memory_get_peak_usage(true);
        $this->initialTimestamp = \microtime(true);

        $this->benchmark();
    }

    public function __destruct()
    {
        \fclose($this->statusHandle);
        echo \json_encode($this->snapshots, self::JSON_FLAGS), \PHP_EOL;
    }

    public function benchmark(): void
    {
        $this->takeSnapshot('Initial');
        $bytemap = $this->instantiate("\x00");
        for ($i = 0; $i < 2000000; ++$i) {
            $bytemap[] = "\x02";
        }
        \assert("\x02" === $bytemap[42], $this->runtimeId);
        \assert("\x02" === $bytemap[2000000 - 1], $this->runtimeId);
        $this->takeSnapshot('BeforeUnset');
        $bytemap = null;
        $this->takeSnapshot('AfterUnset');
    }

    private function instantiate(...$args): BytemapInterface
    {
        return new $this->impl(...$args);
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
        while (false !== ($buffer = \fgets($this->statusHandle))) {
            [$key, $value] = \explode(':', \trim($buffer), 2);
            $value = \preg_replace('~\\s+~', ' ', \trim($value));
            if (\preg_match('~^[0-9]+ kB$~', $value)) {
                $value = 1024 * \substr($value, 0, -3);
            }
            $this->snapshots[$name][$key] = $value;
        }
    }
};

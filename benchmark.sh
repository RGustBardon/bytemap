#!/bin/bash

# This file is part of the Bytemap package.
#
# (c) Robert Gust-Bardon <robert@gust-bardon.org>
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

# docker volume create --name bytemap-logs
# docker build -t bytemap .
# docker run -it -v bytemap-logs:/var/log/bytemap/ --name bytemap bytemap
# docker cp bytemap:/var/log/bytemap/benchmark build/benchmark
# docker rm bytemap

LOG_DIR=/var/log/bytemap/benchmark

if [ $# -eq 0 ]
then
    BENCHMARKS=$(php benchmark.php --list-benchmarks)
else
    BENCHMARKS=$1
fi

mkdir -p \
    "$LOG_DIR/DsDequePolyfill" \
    "$LOG_DIR/DsVectorPolyfill" \
    "$LOG_DIR/ArrayBytemap" \
    "$LOG_DIR/SplBytemap" \
    "$LOG_DIR/DsDequeExtension" \
    "$LOG_DIR/DsVectorExtension" \
    "$LOG_DIR/Bytemap"

for benchmark in $BENCHMARKS
do
    php benchmark.php 'Bytemap\Benchmark\ArrayBytemap' $benchmark | tee "$LOG_DIR/ArrayBytemap/$benchmark.json"
    php benchmark.php 'Bytemap\Benchmark\SplBytemap' $benchmark | tee "$LOG_DIR/SplBytemap/$benchmark.json"
    php benchmark.php 'Bytemap\Benchmark\DsDequeBytemap' $benchmark | tee "$LOG_DIR/DsDequePolyfill/$benchmark.json"
    php benchmark.php 'Bytemap\Benchmark\DsVectorBytemap' $benchmark | tee "$LOG_DIR/DsVectorPolyfill/$benchmark.json"
done

docker-php-ext-enable ds

for benchmark in $BENCHMARKS
do
    php benchmark.php 'Bytemap\Benchmark\DsDequeBytemap' $benchmark | tee "$LOG_DIR/DsDequeExtension/$benchmark.json"
    php benchmark.php 'Bytemap\Benchmark\DsVectorBytemap' $benchmark | tee "$LOG_DIR/DsVectorExtension/$benchmark.json"
    php benchmark.php 'Bytemap\Bytemap' $benchmark | tee "$LOG_DIR/Bytemap/$benchmark.json"
done
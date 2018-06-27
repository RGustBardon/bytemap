#!/bin/sh

# This file is part of the Bytemap package.
#
# (c) Robert Gust-Bardon <robert@gust-bardon.org>
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

# docker build -t bytemap . && docker run -it --rm --name bytemap bytemap

php benchmark.php 'Bytemap\Benchmark\DsBytemap'
docker-php-ext-enable ds
php benchmark.php 'Bytemap\Benchmark\ArrayBytemap'
php benchmark.php 'Bytemap\Benchmark\DsBytemap'
php benchmark.php 'Bytemap\Bytemap'
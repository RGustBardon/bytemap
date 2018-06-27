# This file is part of the Bytemap package.
#
# (c) Robert Gust-Bardon <robert@gust-bardon.org>
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

FROM php:latest
RUN pecl install ds
COPY . /usr/src/bytemap
WORKDIR /usr/src/bytemap
CMD [ "./benchmark.sh" ]
# This file is part of Minz.
# Copyright 2020-2025 Marien Fressinaud
# SPDX-License-Identifier: AGPL-3.0-or-later

FROM php:8.2-cli

ENV COMPOSER_HOME /tmp

RUN apt-get update && apt-get install -y \
        git \
        libicu-dev \
        libzip-dev \
        unzip \
        libpq-dev \
    && pecl install xdebug \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl gettext pcntl zip pdo pdo_pgsql \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini;

COPY --from=composer/composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

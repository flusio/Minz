FROM php:7.4-cli

ENV COMPOSER_HOME /tmp

RUN apt-get update && apt-get install -y \
        git \
        libicu-dev \
        libzip-dev \
        unzip \
        libpq-dev \
    && pecl install xdebug-3.1.6 \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl gettext zip pdo pdo_pgsql \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini;

COPY --from=composer/composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

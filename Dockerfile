FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends unzip \
	&& docker-php-ext-install mysqli pdo_mysql \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock* /tmp/always-beautiful-composer/
RUN COMPOSER_VENDOR_DIR=/opt/always-beautiful-vendor composer install \
	--working-dir=/tmp/always-beautiful-composer \
	--no-dev --no-interaction --prefer-dist --optimize-autoloader


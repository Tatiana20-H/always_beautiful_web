FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends git unzip \
	&& docker-php-ext-install mysqli pdo_mysql \
	&& php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');" \
	&& php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
	&& rm /tmp/composer-setup.php \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copia todos los archivos de tu proyecto al contenedor
COPY . /var/www/html/

RUN composer install --no-dev --no-interaction --classmap-authoritative


FROM php:5.6-fpm

RUN apt-get update && apt-get install -y git zip

RUN docker-php-ext-install opcache
RUN pecl install redis-4.3.0 && docker-php-ext-enable redis
RUN pecl install xdebug-2.5.5 && docker-php-ext-enable xdebug

COPY www.conf /usr/local/etc/php-fpm.d/
COPY docker-php-ext-apcu-cli.ini /usr/local/etc/php/conf.d/

COPY --from=composer:2.0 /usr/bin/composer /usr/bin/composer

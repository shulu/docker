FROM php:fpm

COPY ./sources.list.stretch /etc/apt/sources.list

RUN apt-get update \
	&& docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install exif \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install calendar \
    && docker-php-ext-install sockets

# Install Redis extension from source
ENV PHPREDIS_VERSION 4.1.1
COPY ./phpredis-$PHPREDIS_VERSION.tar.gz /tmp/redis.tar.gz
RUN ls /tmp
RUN mkdir -p /tmp/redis \
    && tar -xf /tmp/redis.tar.gz -C /tmp/redis --strip-components=1 \
    && rm /tmp/redis.tar.gz \
    && docker-php-ext-configure /tmp/redis --enable-redis \
    && docker-php-ext-install /tmp/redis \
    && rm -r /tmp/redis

# Install Swoole extension from source
ENV SWOOLE_VERSION 4.1.2
COPY ./swoole-src-$SWOOLE_VERSION.tar.gz /tmp/swoole.tar.gz
RUN ls /tmp
RUN mkdir -p /tmp/swoole \
    && tar -xf /tmp/swoole.tar.gz -C /tmp/swoole --strip-components=1 \
    && rm /tmp/swoole.tar.gz \
    && docker-php-ext-configure /tmp/swoole --enable-swoole \
    && docker-php-ext-install /tmp/swoole \
    && rm -r /tmp/swoole




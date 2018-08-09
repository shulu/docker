FROM php:fpm

COPY ./sources.list.stretch /etc/apt/sources.list

RUN apt-get update

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



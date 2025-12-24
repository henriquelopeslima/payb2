FROM php:8.4-fpm-alpine

ARG PUID=1000
ARG PGID=1000
ARG XDEBUG=off

RUN apk add --update --no-cache \
        libintl icu icu-dev postgresql-dev pcre-dev linux-headers $PHPIZE_DEPS \
    && docker-php-ext-install intl pdo pdo_pgsql \
    && cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

RUN if [ "$XDEBUG" != "off" ]; then \
        pecl install xdebug \
        && docker-php-ext-enable xdebug; \
    fi

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN addgroup -g ${PGID} appgroup \
    && adduser -D -G appgroup -u ${PUID} appuser

WORKDIR /code

# Directory for custom PHP and Xdebug configs (mounted via volume)
# /usr/local/etc/php/conf.d/ already exists in the base image

USER appuser

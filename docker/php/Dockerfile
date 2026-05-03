FROM php:8.3-fpm-alpine

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        postgresql-dev \
        oniguruma-dev \
        linux-headers \
    && apk add --no-cache \
        bash \
        git \
        curl \
        libpq \
        libzip \
        icu-libs \
        oniguruma \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo \
        pdo_pgsql \
        pgsql \
        sockets \
        zip \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN addgroup -g ${GROUP_ID} app \
    && adduser -D -u ${USER_ID} -G app -s /bin/bash app \
    && mkdir -p /var/www/html /home/app/.composer \
    && chown -R app:app /var/www/html /home/app

USER app
WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]

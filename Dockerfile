FROM php:8.2.1-zts-bullseye AS php-base

FROM php-base AS php-with-extensions

RUN --mount=type=cache,target=/var/cache/apt,sharing=private \
    apt update \
    && apt install -y git libsodium-dev libzip-dev curl

RUN pecl install pcov

RUN docker-php-ext-install pcntl sodium zip
RUN docker-php-ext-enable pcov

WORKDIR /app

FROM php-with-extensions AS composer-base

COPY --link composer.json .
COPY --link composer.lock .

FROM composer-base AS composer-prod

RUN --mount=type=cache,target=/root/.composer \
    --mount=from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
    composer validate && composer install --prefer-dist --no-dev --no-scripts

FROM composer-base AS composer-dev

RUN --mount=type=cache,target=/root/.composer \
    --mount=from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
    composer install --prefer-dist --no-scripts

FROM composer-base AS composer-update
RUN --mount=type=cache,target=/root/.composer \
    --mount=from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
    composer update --prefer-dist --no-scripts

FROM scratch AS composer-output

COPY --link --from=composer-dev /app/composer.lock .
COPY --link --from=composer-dev /app/vendor vendor

FROM scratch AS composer-output-updated

COPY --link --from=composer-update /app/composer.lock .
COPY --link --from=composer-update /app/vendor vendor

FROM scratch AS migrations-output

COPY --link --from=php-with-extensions /app/resources/migrations resources/migrations/

FROM php-with-extensions AS php-with-code

COPY --link resources resources
COPY --link src src
COPY --link --from=composer-prod /app/vendor vendor

RUN --mount=type=cache,target=/root/.composer \
    --mount=source=composer.json,target=composer.json \
    --mount=source=composer.lock,target=composer.lock \
    --mount=from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
    composer install --prefer-dist --no-dev

FROM php-with-code AS php-toolbox

COPY --link phpunit.xml .
COPY --link dummy_app dummy_app
COPY --link test test
COPY --link --from=composer-dev /app/vendor vendor

RUN --mount=type=cache,target=/root/.composer \
    --mount=source=composer.json,target=composer.json \
    --mount=source=composer.lock,target=composer.lock \
    --mount=from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
    composer install --prefer-dist

ENTRYPOINT ["./vendor/bin/phpunit"]

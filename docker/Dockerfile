# syntax=docker/dockerfile:1
ARG PHP_VER=8.5
ARG COMPOSER_VER=2.9
ARG SYMFONY_CLI_VER=v5.17
ARG PIE_VER=1.4.10
# SHA256 of pie.phar for the pinned PIE_VER (verify integrity of the download).
ARG PIE_PHAR_SHA256=b88792235c8e80be568436d4cb043b49fd1869c89b64e83d23e2882ae19d70a8
ARG PHP_USER=www-data

# WORKDIR /var/www/html is set in php image
ARG APP_DIR=/var/www/html

FROM php:${PHP_VER}-apache AS php-base
ARG APP_DIR
RUN --mount=type=cache,target=/var/cache/apt apt-get update \
    && apt-get install -y libicu-dev libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli pdo pdo_mysql intl gd zip
RUN echo 'short_open_tag = Off' > /usr/local/etc/php/conf.d/short-open-tag.ini \
    && echo 'date.timezone = Europe/Kyiv' > /usr/local/etc/php/conf.d/timezone.ini \
    && echo 'memory_limit = 256M' > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo 'expose_php = Off' > /usr/local/etc/php/conf.d/security.ini
RUN a2enmod rewrite headers
RUN sed -i 's/ServerTokens OS/ServerTokens Prod/' /etc/apache2/conf-available/security.conf \
    && sed -i 's/ServerSignature On/ServerSignature Off/' /etc/apache2/conf-available/security.conf
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf \
    && sed -i 's/:80/:8080/g' /etc/apache2/sites-available/*.conf
ENV APACHE_DOCUMENT_ROOT=${APP_DIR}/public
RUN sed -ri -e "s!${APP_DIR}!\${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

EXPOSE 8080/tcp

FROM composer:${COMPOSER_VER} AS composer-pinned

FROM ghcr.io/symfony-cli/symfony-cli:${SYMFONY_CLI_VER} AS symfony-cli-pinned

FROM php-base AS php-base-with-composer
COPY --link --from=composer-pinned /usr/bin/composer /usr/bin/composer
ENV COMPOSER_PROCESS_TIMEOUT=600
# Dummy values so cache:clear (run via post-install-cmd)
# can resolve these env vars at build time.
# All overridden at runtime.
ENV APP_SECRET="build_time_dummy_secret"
ENV DATABASE_URL="mysql://user:pass@127.0.0.1:3306/db?charset=utf8mb4"
ENV DEFAULT_URI="http://localhost"
ENV GOOGLE_CLIENT_ID="build_time_dummy"
ENV GOOGLE_CLIENT_SECRET="build_time_dummy_secret"
ENV MAILER_DSN="smtp://127.0.0.1:1025"
ENV MESSENGER_TRANSPORT_DSN="doctrine://default?queue_name=async"
ENV REDIS_RATE_LIMITER_URL="redis://127.0.0.1:6379"
ENV REDIS_SESSION_URL="redis://127.0.0.1:6379"
ENV REDIS_URL="redis://127.0.0.1:6379"

FROM php-base-with-composer AS php-dev
ARG PIE_VER
ARG PIE_PHAR_SHA256
ENV APP_ENV=dev
ENV APP_DEBUG=1
COPY --link \
    --from=symfony-cli-pinned \
    /usr/local/bin/symfony /usr/local/bin/symfony
# Install PCOV via PIE (the official replacement for the deprecated PECL).
# The pinned pie.phar is verified against its published SHA256 before use.
RUN --mount=type=cache,target=/var/cache/apt apt-get update \
    && apt-get install -y git unzip curl nodejs npm mc nano \
    && curl -fL --output /usr/local/bin/pie "https://github.com/php/pie/releases/download/${PIE_VER}/pie.phar" \
    && echo "${PIE_PHAR_SHA256}  /usr/local/bin/pie" | sha256sum --check --strict \
    && chmod +x /usr/local/bin/pie \
    && pie install pecl/pcov

FROM php-base-with-composer AS php-base-with-app-test
ARG APP_DIR
ENV APP_ENV=test
ENV APP_DEBUG=1
WORKDIR ${APP_DIR}
COPY composer.json composer.lock symfony.lock ./
RUN --mount=type=cache,target=/root/.composer/cache composer install \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative \
    && composer run-script post-install-cmd

FROM php-dev AS rating-app-test
ARG APP_DIR
ARG PHP_USER
ENV APP_ENV=test
ENV APP_DEBUG=1
COPY --link docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
COPY --link --from=php-base-with-app-test ${APP_DIR}/package.json ${APP_DIR}/package-lock.json ${APP_DIR}
RUN npm install
COPY --link --from=php-base-with-app-test ${APP_DIR} ${APP_DIR}
RUN mkdir -p ${APP_DIR}/var/cache ${APP_DIR}/var/log \
    && chown -R ${PHP_USER}:${PHP_USER} ${APP_DIR}/var
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

FROM php-base-with-composer AS php-base-with-app-prod
ARG APP_DIR
ENV APP_ENV=prod
ENV APP_DEBUG=0
WORKDIR ${APP_DIR}
COPY composer.json composer.lock symfony.lock ./
RUN --mount=type=cache,target=/root/.composer/cache composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && composer run-script post-install-cmd \
    && php bin/console asset-map:compile

FROM php-base AS rating-app
ARG APP_DIR
ARG PHP_USER
ENV APP_ENV=prod
ENV APP_DEBUG=0
COPY --link docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
COPY --link --from=php-base-with-app-prod ${APP_DIR} ${APP_DIR}
RUN mkdir -p ${APP_DIR}/var/cache ${APP_DIR}/var/log \
    && chown -R ${PHP_USER}:${PHP_USER} ${APP_DIR}/var
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

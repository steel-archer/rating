FROM php:8.5-apache AS php-base
# WORKDIR /var/www/html is set in php image
RUN apt-get update \
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
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

EXPOSE 8080/tcp

FROM php-base AS php-dev
COPY --link --from=composer:2.9 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_PROCESS_TIMEOUT=600
COPY --link \
    --from=ghcr.io/symfony-cli/symfony-cli:v5.17 \
    /usr/local/bin/symfony /usr/local/bin/symfony
RUN apt-get update \
    && apt-get install -y git unzip curl nodejs npm mc nano \
    && git clone --depth 1 https://github.com/krakjoe/pcov.git /tmp/pcov \
    && cd /tmp/pcov && phpize && ./configure && make && make install \
    && docker-php-ext-enable pcov \
    && rm -rf /tmp/pcov

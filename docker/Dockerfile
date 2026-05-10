FROM php:8.5-apache
RUN apt-get update \
    && apt-get install -y libicu-dev git unzip curl nodejs npm \
    && docker-php-ext-install mysqli pdo pdo_mysql intl \
    && pecl install redis pcov && docker-php-ext-enable redis pcov
RUN echo 'short_open_tag = Off' > /usr/local/etc/php/conf.d/short-open-tag.ini
RUN echo 'date.timezone = Europe/Kyiv' > /usr/local/etc/php/conf.d/timezone.ini
RUN echo 'memory_limit = 256M' > /usr/local/etc/php/conf.d/memory-limit.ini
COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer
RUN git config --global user.email "dev@rating.local" && git config --global user.name "Rating Dev"
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

COPY --link \
    --from=ghcr.io/symfony-cli/symfony-cli:latest \
    /usr/local/bin/symfony /usr/local/bin/symfony

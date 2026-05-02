FROM php:8.5-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN a2enmod rewrite

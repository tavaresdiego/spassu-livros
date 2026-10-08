FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip gd opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN { \
        echo 'date.timezone=America/Sao_Paulo'; \
        echo 'memory_limit=512M'; \
    } > /usr/local/etc/php/conf.d/app.ini

ARG UID=1000
ARG GID=1000
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} -g ${GID} www-data \
    && mkdir -p /var/www/.composer && chown -R www-data:www-data /var/www

ENV TZ=America/Sao_Paulo COMPOSER_HOME=/var/www/.composer
WORKDIR /var/www/html
USER www-data

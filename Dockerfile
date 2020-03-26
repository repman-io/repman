FROM php:7.4-fpm

ARG TIMEZONE

# install composer and extensions: pdo_pgsql, pcov, intl, zip
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    git zip unzip libpq-dev libicu-dev libzip-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    pecl install pcov && \
    docker-php-ext-enable pcov && \
    docker-php-ext-configure pdo_pgsql --with-pdo-pgsql && \
    docker-php-ext-configure intl && \
    docker-php-ext-configure zip && \
    docker-php-ext-install pdo_pgsql && \
    docker-php-ext-install intl && \
    docker-php-ext-install zip

# set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && \
    echo ${TIMEZONE} > /etc/timezone && \
    printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini && "date"

RUN mkdir /app
WORKDIR /app

COPY . .

RUN composer install --optimize-autoloader

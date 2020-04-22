FROM php:7.4-fpm-alpine

ARG TIMEZONE="UTC"

SHELL ["sh", "-eo", "pipefail", "-c"]

# install composer and extensions: pdo_pgsql, intl, zip
RUN apk update && \
    apk add --no-cache -q \
    $PHPIZE_DEPS \
    bash \
    git \
    zip \
    unzip \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    openssh-client \
    && \
    curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/local/bin --filename=composer && \
    docker-php-ext-configure pdo_pgsql --with-pdo-pgsql && \
    docker-php-ext-configure intl && \
    docker-php-ext-configure zip && \
    docker-php-ext-install pdo_pgsql && \
    docker-php-ext-install intl && \
    docker-php-ext-install zip && \
    rm -rf /var/cache/apk/*

# set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && \
    echo ${TIMEZONE} > /etc/timezone && \
    printf '[PHP]\ndate.timezone = "%s"\n', "$TIMEZONE" > \
    /usr/local/etc/php/conf.d/tzone.ini && "date"

# automatically add new host keys to the user known hosts
RUN printf "Host *\n    StrictHostKeyChecking no" > /etc/ssh/ssh_config

RUN mkdir /app
WORKDIR /app

COPY . .

RUN composer install --optimize-autoloader --no-dev

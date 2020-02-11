FROM php:7.4.1

RUN apt-get update && apt-get install -y git zip && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# pdo_pgsql
RUN apt-get install -y libpq-dev && \
    docker-php-ext-configure pdo_pgsql --with-pdo-pgsql && \
    docker-php-ext-install pdo_pgsql

# pcov
RUN pecl install pcov && \
    docker-php-ext-enable pcov

# intl
RUN apt-get install -y libicu-dev && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl

# zip
RUN apt-get install -y unzip libzip-dev && \
    docker-php-ext-configure zip && \
    docker-php-ext-install zip

# symfony tool
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony/bin/symfony /usr/local/bin/symfony

COPY . /app
WORKDIR /app

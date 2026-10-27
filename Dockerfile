# the minor line is pinned, patch releases (security fixes) arrive with every rebuild
FROM php:8.4-fpm-alpine

ARG TIMEZONE="UTC"
ARG PHP_EXTENSION_INSTALLER_VERSION="2.12.3"

SHELL ["sh", "-eo", "pipefail", "-c"]

# su-exec: the containers start as root to fix ownership of the mounted var/, then drop to www-data
RUN apk update && \
    apk add --no-cache -q \
    bash \
    git \
    subversion \
    openssh-client \
    su-exec

RUN curl -sSLf \
    -o /usr/local/bin/install-php-extensions \
    https://github.com/mlocati/docker-php-extension-installer/releases/download/${PHP_EXTENSION_INSTALLER_VERSION}/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions opcache pdo_pgsql intl zip @composer && \
    rm /usr/local/bin/install-php-extensions

# set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && \
    echo ${TIMEZONE} > /etc/timezone && \
    printf '[PHP]\ndate.timezone = "%s"\n' "$TIMEZONE" > \
    /usr/local/etc/php/conf.d/tzone.ini && "date"

# set memory limit
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# PHP2Shell (CVE-2026-109618, CVE-2026-109619): remote code execution, exploited in the wild, fixed in
# 8.4.27; a cached base image is older until it is pulled again. Session upload progress, which Repman
# does not use, is off as PHP advises against CVE-2026-115560, which has no fix yet.
RUN php -r 'exit(version_compare(PHP_VERSION, "8.4.27", "<") ? 1 : 0);' || \
    { echo "PHP $(php -r 'echo PHP_VERSION;') has known remote code execution flaws: build with docker compose build --pull"; exit 1; }
RUN echo "session.upload_progress.enabled=0" > /usr/local/etc/php/conf.d/session-upload-progress.ini

# hide X-Powered-By in reponse header
RUN echo "expose_php=off" > /usr/local/etc/php/conf.d/expose.ini

# accept the host key of a git server on first contact, but refuse it if it changes later
RUN printf "\nHost *\n    StrictHostKeyChecking accept-new\n" >> /etc/ssh/ssh_config

RUN mkdir /app
WORKDIR /app

COPY . .

ENV APP_ENV=prod

RUN composer install --optimize-autoloader --no-dev; composer clear-cache && \
    chown -R www-data:www-data /app/var

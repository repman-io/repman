ARG IMAGE=041193065457.dkr.ecr.eu-north-1.amazonaws.com/php:8.2-alpine316-make

FROM $IMAGE

ARG APPS
ARG APP_ENV
ARG APP

RUN docker-php-ext-enable amqp sockets pcntl

ADD --chown=dp ./ /var/www/html/
ADD .docker/production/artifacts/ /

USER dp
WORKDIR /var/www/html

RUN sudo -E datadog-install

RUN cd /var/www/html \
	&& cp .env .env.prod \
	&& php composer.phar install -n --prefer-dist --no-scripts -o --no-dev

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint", "-f", "/var/www/html/.docker/production/Makefile"]

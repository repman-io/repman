# Repman - PHP Repository Manager

Repman is a PHP repository manager. Main features:

- work as proxy for packagist.org (speed up your local builds)
- host your private packages
- allow to create individual access tokens
- import private packages from GitHub, GitLab and Bitbucket with one click

## Requirements

- PHP >= 7.4
- PostgreSQL 11
- `var` dir must be writeable
- any web server

## Installation

```bash
git clone git@github.com:buddy-works/repman.git
cd repman
composer install
```

## Workers

To process messages asynchronously you must run worker:

```bash
bin/console messenger:consume async
```

Read more: [deploying to production](https://symfony.com/doc/current/messenger.html#deploying-to-production)

## Usage

Navigate your browser to instance address, you will see home page with usage instructions.

## Local proxy

On dev env you may want to enable proxy to allow to create subdomains and tests composer organizations:

```bash
composer proxy-setup
```

This will create `repman.wip` domain. Then you can add other domains with:

```bash
symfony proxy:domain:attach your-organization.repman
```

### CLI commands

- `bin/console repman:metadata:clear-cache` - clear packages metadata cache (json files)

## Integration

Callbacks:

- `/auth/{provider}/check`
- `/register/{provider}/check`
- `/user/token/{provider}/check`

### GitHub

Scopes:

- registration: `user:email`
- repositories: `read:org`, `repo`

### GitLab

Scopes:

- registration: `read_user`
- repositories: `api`

### Bitbucket

Scopes:

- registration: `email`
- repositories: `repository`, `webhook`

## Docker

- Override with `docker-compose.override.yml` if needed. You can change app domain in `services.nginx.build.args.DOMAIN`.
- Take a look at `.env.docker` and make sure that `APP_HOST` matches your domain.

Build and start

```bash
docker-compose build
docker-compose up
```

If you wish to use your own certificate put `server.key` and `server.crt` in `docker/nginx/cert` folder.

Otherwise generated self-sign certificate will be used.

```bash
# copy certificate so it won't be regenerated anymore
docker cp repman_nginx_1:/cert docker/nginx
```

### Simple local DNS

Dnsmasq

```conf
# /usr/local/etc/dnsmasq.conf

address=/wip/127.0.0.1
port=53
no-resolv
```

```conf
# /etc/resolver/wip

nameserver 127.0.0.1
```

```bash
sudo brew services start dnsmasq
sudo ifconfig en0 down && sudo ifconfig en0 up
```

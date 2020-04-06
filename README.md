# Repman - PHP Repository Manager

[![buddy pipeline](https://app.buddy.works/repman/repman/pipelines/pipeline/244546/badge.svg?token=dbd28b3ece0d16aba095b8a33d0893d15f0403fbcc285a2a1a175cc77f7c94a8 "buddy pipeline")](https://app.buddy.works/repman/repman/pipelines/pipeline/244546)

Repman is a PHP repository manager. Main features:

- free and open source
- work as proxy for packagist.org (speed up your local builds)
- host your private packages
- allow to create individual access tokens
- import private packages from GitHub, GitLab and Bitbucket with one click
- host your own instance (multiple deployment solutions)

Documentation: [https://repman.io/docs](https://repman.io/docs)

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

- Override with `docker-compose.override.yml` if needed.
- Set your domain (`APP_HOST`) in `.env.docker`.

If you wish to use your own certificate put key and certificate in:

- `docker/nginx/ssl/private/server.key`
- `docker/nginx/ssl/certs/server.crt`

Otherwise self-sign certificate will be generated.

To start all containers run:

```bash
docker-compose up
```

---

made with ❤️ by [Buddy](https://buddy.works)

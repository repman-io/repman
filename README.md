# Repman - PHP Repository Manager

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.4-8892BF.svg)](https://php.net/)
[![Uptime Robot ratio (24h)](https://badgen.net/uptime-robot/day/m784813562-93c7dab381e24ccdb679c5d2)](https://stats.uptimerobot.com/QAMQli6XQM)
[![buddy pipeline](https://app.buddy.works/repman/repman/pipelines/pipeline/244549/badge.svg?token=ac6f2fe807ba6b7ad90902f5bda1c1fb8445014757f82e1867f2cad2fd612035 "buddy pipeline")](https://app.buddy.works/repman/repman/pipelines/pipeline/244549)
[![codecov](https://codecov.io/gh/repman-io/repman/branch/master/graph/badge.svg)](https://codecov.io/gh/repman-io/repman)
[![Hits-of-Code](https://hitsofcode.com/github/repman-io/repman)](https://hitsofcode.com/view/github/repman-io/repman)
[![Maintainability](https://api.codeclimate.com/v1/badges/23a93132c8273cabf9eb/maintainability)](https://codeclimate.com/github/repman-io/repman/maintainability)
[![Docker Pulls](https://img.shields.io/docker/pulls/buddy/repman)](https://hub.docker.com/r/buddy/repman)
![License](https://img.shields.io/github/license/repman-io/repman)

**Repman** is a PHP repository manager. Main features:

- free and open source
- works as a proxy for **packagist.org** (speeds up your local builds)
- hosts your private packages
- allows to create individual access tokens
- supports private package import from **GitHub**, **GitLab** and **Bitbucket** with one click
- REST API
- security scanner (with e-mail reports)

Documentation: [https://repman.io/docs/](https://repman.io/docs/)

## Requirements

- PHP >= 8.4 with the `intl`, `mbstring`, `pdo_pgsql` and `zip` extensions
- PostgreSQL 11 - 17
- `var` dir must be writeable
- any web server

## Installation

### Docker

[https://repman.io/docs/standalone/#docker-installation](https://repman.io/docs/standalone/#docker-installation)

`docker-compose.yml` builds the image from this checkout: `docker compose build --pull && docker compose up -d`.
Rebuild with `--pull` whenever PHP publishes a security release, the build refuses a PHP version with known
remote code execution flaws.

#### Private Git repositories over SSH

Packages are synchronized by the `consumer` service as `www-data` (home `/home/www-data`). Give it
a deploy key in a `docker-compose.override.yml` next to `docker-compose.yml`:

```yaml
services:
  consumer:
    volumes:
      - ./ssh:/home/www-data/.ssh:ro
```

`./ssh` holds the private key (`id_ed25519` or `id_rsa`) and optionally a `known_hosts` file. On Linux
the key must belong to the container's `www-data` (uid 82): `sudo chown -R 82:82 ssh && chmod 600 ssh/id_*`.
Then add the package as *Git* with an SSH URL such as `git@git.example.com:team/package.git`. Hosts are
trusted on first contact and refused if their key changes later; with the read-only mount ssh cannot
record them and prints a warning, which `known_hosts` avoids. Such repositories have no archives, so
Composer installs them from source and developers need access to the repository too. The security scan
checks the `composer.lock` committed in the repository: the sync keeps it for the latest release and the
default branch, so packages synchronized before this release need one more synchronization.

#### Upgrading a Docker installation

Run these steps in the checkout your installation runs from; without one (`docker run` or a compose file of
your own), see [Switching from the `buddy/repman` image without a checkout](#switching-from-the-buddyrepman-image-without-a-checkout).
They were tested by upgrading the 1.4.0 release
(its PostgreSQL 11 database is upgraded in place) and a `master` checkout with organizations, packages,
tokens, invitations and download statistics: all data was kept, packages were added and installed
afterwards. See [UPGRADE.md](UPGRADE.md) for what changed.

1. Back up the database and `var/` (packages, proxy cache, logs) while the old version runs:

   ```bash
   docker compose exec -T database pg_dump -U main -d main -Fc > repman-backup.dump
   tar czf repman-var-backup.tgz var
   ```

2. Stop the stack. Containers are removed, the database volume and `var/` are kept:

   ```bash
   docker compose down
   ```

3. Get the new code. Your own settings go to `.env.docker.local`, which git ignores and which wins
   over the defaults in `.env.docker`, so later updates never conflict with them:

   ```bash
   git diff -U0 -- .env.docker | grep '^+[A-Z]' | cut -c2- >> .env.docker.local   # only the values you changed
   git stash                       # parks your edits of tracked files; git stash show -p lists them
   git pull                        # or check out the release tag
   ```

   Check `.env.docker.local`: it holds your `APP_HOST`, `APP_SECRET`, mailer and OAuth settings. If you
   had changed `docker-compose.yml` (ports, a database image), put those changes into a
   `docker-compose.override.yml` instead.

4. Build and start the new version (`--pull` fetches the current PHP patch release):

   ```bash
   docker compose build --pull
   docker compose up -d
   docker compose ps               # wait until app is "healthy"
   ```

   On the first start a database created by an older PostgreSQL (the 1.x releases shipped 11) is
   upgraded to PostgreSQL 17 in place; `docker compose logs -f database` shows the progress. Then the
   app hands `var/` to `www-data`, rebuilds the cache and runs the database migrations
   (`docker compose logs -f app`).

5. Check the result and resynchronize packages, so they pick up the fixes of this release:

   ```bash
   docker compose exec app su-exec www-data bin/console doctrine:migrations:status
   docker compose exec app su-exec www-data bin/console repman:package:synchronize-all
   ```

   Packages whose first synchronization failed have no name yet and are skipped by
   `synchronize-all`; use *Update* in their *Actions* menu on the package list.

Rolling back (tested back to 1.4.0): the upgraded database can no longer be read by an older PostgreSQL,
so the backup is restored into a new one.

```bash
docker compose down
git checkout <the tag or commit you ran before>
git stash pop                                  # your old .env.docker and docker-compose.yml edits
docker volume rm <directory name>_postgres-data
docker compose up -d
docker compose exec -T database pg_restore -U main -d main --clean --if-exists < repman-backup.dump
tar xzf repman-var-backup.tgz
```

#### Switching from the `buddy/repman` image without a checkout

For installations that ran the published image with `docker run` or a compose file of their own. Their
containers, volumes and database are named differently and their settings live in the container's
environment, so the data moves over through a database dump and an archive of `var/`. Tested by switching a
1.4.0 installation of four `docker run` containers with its own database user and named volumes: all data
was kept. Replace `repman-app` and `repman-db` below with the names of your app and database containers
(`docker ps`).

1. Note what the old setup uses, while it runs:

   ```bash
   docker inspect -f '{{range .Config.Env}}{{println .}}{{end}}' repman-app > old-env.txt
   docker inspect -f '{{range .Mounts}}{{if .Name}}{{.Name}}{{else}}{{.Source}}{{end}} -> {{.Destination}}{{println}}{{end}}' repman-app
   ```

   `old-env.txt` holds your settings; `DATABASE_URL` in it names the database user, password and
   database. The second command shows what is mounted at `/app/var` (a volume name or a directory).

2. Back up the database and `var/`. With the database in a container (user and database from
   `DATABASE_URL`; `<var>` is the volume name or directory from step 1):

   ```bash
   docker exec repman-db pg_dump -U <user> -d <database> -Fc > repman-backup.dump
   docker run --rm -v <var>:/data alpine tar czf - -C /data . > repman-var-backup.tgz
   ```

3. Stop the old containers (`docker stop`, or `docker compose down` where its compose file is). Keep them
   until the new version runs: starting them again is the way back.

4. Get the new version and take over the settings and the files:

   ```bash
   git clone https://github.com/repman-io/repman.git && cd repman
   grep -E '^(APP_|MAILER_|OAUTH_|SENTRY_|GA4_|STORAGE_|EWZ_|PROXY_|PACKAGES_|MESSENGER_|TRUSTED_|COMPOSER_AUTH)' ../old-env.txt > .env.docker.local
   tar xzf ../repman-var-backup.tgz -C var
   ```

   Check `.env.docker.local`. `DATABASE_URL` is left out on purpose: the database of the new setup gets the
   dump in the next step. If your database is not a container of this setup but a server of its own,
   add your `DATABASE_URL` and skip the restore; any PostgreSQL from 11 up works.

5. Build, restore the database into the new one, then start everything:

   ```bash
   docker compose build --pull
   docker compose up -d --wait database
   docker compose exec -T database pg_restore -U main -d main --no-owner --no-acl < ../repman-backup.dump
   docker compose up -d
   ```

   Then check the result and resynchronize as in step 5 of the upgrade above; artifact packages can be
   installed again only after that. Rolling back: `docker compose down` here, then `docker start` the old
   containers, which still have their data. Certificates: see
   [HTTPS and Let's Encrypt](#https-and-lets-encrypt); a certificate of your own goes to
   `docker/nginx/ssl/certs/server.crt` and `docker/nginx/ssl/private/server.key`.

### Ansible

[https://repman.io/docs/standalone/#ansible-playbooks-installation](https://repman.io/docs/standalone/#ansible-playbooks-installation)

### Manual

```bash
git clone git@github.com:repman-io/repman.git
cd repman
composer install
```

Setup database:

```
bin/console doctrine:migrations:migrate #for postgres
bin/console doctrine:schema:create #for sqlite init as migrations are only postgres-compatible
bin/console messenger:setup-transports
```

## Configuration

### Mailer

To configure mailer transport, enter connection details in the `MAILER_DSN` environment variable

```
MAILER_DSN=smtp://user:pass@smtp.example.com
```

Read more: [transport setup](https://symfony.com/doc/current/mailer.html#transport-setup)

In addition, setup also `MAILER_SENDER` environment variable

```
MAILER_SENDER=mail_from@example.com
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
- `bin/console repman:create:admin <email> [<password>]` - create a new user with admin privileges
- `bin/console repman:create:user <email> [<password>]` - create a new (normal) user
- `bin/console repman:proxy:sync-releases` - deprecated, does nothing (packagist.org no longer serves its release feed to Repman)
- `bin/console repman:security:scan-all` - scan all synchronized packages
- `bin/console repman:security:update-db` - ask Packagist's security advisories API for advisories published since the last run, scan all packages if there are any
- `bin/console repman:package:synchronize <packageId>` - synchronize given package
- `bin/console repman:package:clear-old-dists` - clear old private dev distributions files

## API Integration

Callbacks:

- `/auth/{provider}/check`
- `/register/{provider}/check`
- `/user/token/{provider}/check`

### GitHub

Scopes:

- registration: `user:email`
- repositories: `read:org`, `repo`

Packages added as plain *Git* from github.com are read through the GitHub API, which allows 60 anonymous
calls an hour; past that their synchronization fails with "Could not authenticate against github.com". Give
Composer a token (a fine-grained one without permissions is enough for public repositories) in
`.env.docker.local`: `COMPOSER_AUTH='{"github-oauth":{"github.com":"<token>"}}'`. Packages added through
GitHub OAuth keep using their own token.

### GitLab

Scopes:

- registration: `read_user`
- repositories: `api`

### Bitbucket

Scopes:

- registration: `email`
- repositories: `repository`, `webhook`

## Self-hosted GitLab

To integrate with self-hosted GitLab, enter the instance url in the `APP_GITLAB_API_URL` environment variable

```
APP_GITLAB_API_URL='https://gitlab.organization.lan'
```

## Self-hosted Storage

By default, Repman stores packages locally on disk. For production deployments, you can use S3-compatible storage.

### Local Storage (Default)

```env
STORAGE_SOURCE=storage.local
PROXY_DIST_DIR=%kernel.project_dir%/var/proxy
PACKAGES_DIST_DIR=%kernel.project_dir%/var/repo
```

### S3 Storage

To use AWS S3 or S3-compatible storage (MinIO, Ceph, etc.):

```env
STORAGE_SOURCE=storage.s3
STORAGE_AWS_BUCKET=my-bucket-name
STORAGE_AWS_REGION=eu-west-1
# When using S3, use relative paths:
PROXY_DIST_DIR=var/proxy
PACKAGES_DIST_DIR=var/repo
```

#### Authentication Options

**Option 1: AWS Credential Chain (Recommended for AWS/EKS)**

Uses the AWS SDK's default credential provider chain, which supports:

- Environment variables (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`)
- EKS Pod Identity / IRSA (IAM Roles for Service Accounts)
- ECS container credentials
- EC2 instance profile credentials

```env
STORAGE_AWS_OPAQUE_AUTH=false
# No key/secret needed - credentials obtained automatically
```

**Option 2: Explicit Credentials**

For environments where you need to provide credentials directly:

```env
STORAGE_AWS_OPAQUE_AUTH=true
STORAGE_AWS_KEY=your_access_key_id
STORAGE_AWS_SECRET=your_secret_access_key
```

#### S3-Compatible Storage (MinIO, Ceph, etc.)

For self-hosted S3-compatible storage:

```env
STORAGE_AWS_ENDPOINT=https://s3.myhost.com
STORAGE_AWS_PATH_STYLE_ENDPOINT=true
```

## Docker

- Override with `docker-compose.override.yml` if needed.
- Set your domain (`APP_HOST`) and other settings in `.env.docker.local`, it overrides `.env.docker`.

### HTTPS and Let's Encrypt

The web server is [Caddy](https://caddyserver.com) (the compose service is still called `nginx`). Repman
answers on several host names: the app (`APP_HOST`), the Packagist proxy (`repo.APP_HOST`) and one host per
organization (`acme.repo.APP_HOST`, a level deeper, or `acme-repo.APP_HOST` with
`APP_ORGANIZATION_SEPARATOR=-`). A single wildcard does not cover the organizations, and they come and go
with the database, so Caddy gets one certificate per name, the first time the name is asked for, after
asking Repman whether it serves it. Nothing gets a certificate for a name that is not the app, the proxy or
an existing organization.

Caddy picks one of three modes on start:

- **Let's Encrypt** when `ACME_EMAIL` is set in `.env.docker.local`. DNS for `APP_HOST`, `repo.APP_HOST` and
  the organizations has to point at the server (a wildcard record `*.repo.APP_HOST` does it) and port 443
  has to be reachable from the internet. Certificates are renewed in the background and kept in the
  `caddy-data` volume. Try it against the staging CA first with
  `ACME_CA=https://acme-staging-v02.api.letsencrypt.org/directory`.
- **Your own certificate** when `docker/nginx/ssl/certs/server.crt` and `docker/nginx/ssl/private/server.key`
  exist. It has to cover all the names above (e.g. `APP_HOST`, `*.APP_HOST`, `*.repo.APP_HOST`).
- **Caddy's local CA** otherwise, for installs on `localhost` or an internal name: browsers and Composer
  warn until its root certificate (`docker compose exec nginx cat /data/caddy/pki/authorities/local/root.crt`)
  is trusted.

Only port 443 is published. Plain http is redirected to https; publish port 80 in a
`docker-compose.override.yml` to get that redirect (`ports: ['80:80', '443:443', '443:443/udp']`).
`APP_URL_SCHEME=http` serves plain http instead of redirecting it.

To build the image and start all containers run:

```bash
docker compose build --pull
docker compose up -d
```

## Support

In case of any problems, you can use:

- Our documentation: [repman.io/docs](https://repman.io/docs/) - it is also open sourced [github.com/repman-io/repman-docs-pages](https://github.com/repman-io/repman-docs-pages)
- GitHub issue list: [github.com/repman-io/repman/issues](https://github.com/repman-io/repman/issues) - feel free to create a new issue there
- E-mail: contact [at] repman.io

## License

The Repman project is licensed under the terms of the [MIT](LICENSE).

However, Repman includes several third-party Open-Source libraries, which are licensed under their own respective Open-Source licenses.

#### Libraries or projects directly included in Repman

- Tabler: [MIT](https://github.com/tabler/tabler/blob/master/LICENSE)
- Feather: [MIT](https://github.com/feathericons/feather/blob/master/LICENSE)
- Lucide: License: [ISC](https://github.com/lucide-icons/lucide/blob/master/LICENSE)
- Postmark Transactional Email Templates: [MIT](https://github.com/wildbit/postmark-templates/blob/master/LICENSE)
- Libraries dynamically referenced via Composer: run `composer license` to get the latest licensing info about all dependencies.

---

made with ❤️ by [Buddy](https://buddy.works)

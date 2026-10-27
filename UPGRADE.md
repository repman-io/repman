# UPGRADE

## UPGRADE FROM 1.4.x TO THE NEXT RELEASE

- PHP **8.4** is required (was 7.4/8.0), together with the `intl`, `mbstring`, `pdo_pgsql` and `zip`
  extensions. Symfony was raised from 5.4 to 6.4 LTS and Doctrine ORM from 2 to 3; if you made custom
  changes, read [Symfony's UPGRADE-6.0 to 6.4](https://github.com/symfony/symfony/blob/6.4/UPGRADE-6.0.md).
- run the migrations: `bin/console doctrine:migrations:migrate` (package link constraints become TEXT)
- clear the cache: `rm -rf var/cache/*`
- docker-compose.yml now builds the image from the checkout (`docker compose build --pull`, then
  `docker compose up -d`) instead of
  pulling `buddy/repman:1.3.4`, and the containers run as `www-data`. On start, ownership of `var/` is
  handed to `www-data`, so files an older version wrote as root keep working. The crontab moved from
  `docker/crontabs/root` to `docker/crontabs/www-data`.
- docker: the web server is Caddy instead of nginx (the service keeps the name `nginx`). A certificate
  in `docker/nginx/ssl/` is used as before, including the self-signed one the nginx setup generated; set
  `ACME_EMAIL` to get Let's Encrypt certificates instead, see "HTTPS and Let's Encrypt" in the README.
  `docker/logs/nginx` is no longer written (logs: `docker compose logs nginx`) and can be deleted.
- docker: installations that ran the `buddy/repman` image without a git checkout (`docker run`, an own
  compose file) move over with a database dump and an archive of `var/`, see the README.
- docker: your settings belong in `.env.docker.local` (ignored by git, overrides `.env.docker`), compose
  changes in `docker-compose.override.yml`; see the upgrade steps in the README.
- docker: the database runs PostgreSQL 17 (`pgautoupgrade/pgautoupgrade`, the official image plus an
  in-place `pg_upgrade`); a database created by an older PostgreSQL is upgraded on the first start. Back it
  up first: an older PostgreSQL cannot read it afterwards. app waits for the database, consumer and cron
  wait for the migrations.
- the Packagist proxy now fetches metadata from `repo.packagist.org` (packagist.org refuses Repman).
  `repman:proxy:sync-releases` is deprecated and does nothing; remove it from your crontab
  (the Ansible role does).
- PEAR packages can no longer be added or synchronized: Composer 2 removed PEAR support. `pear` left in
  `APP_ALLOWED_PACKAGE_TYPES` is ignored.
- `STORAGE_SOURCE=storage.local.cached` / `storage.s3.cached` still work but no longer cache metadata
  (Flysystem dropped the cache adapter); you can switch to `storage.local` / `storage.s3`.
- `GA_TRACKING` (Universal Analytics, shut down by Google in 2023) was removed; `GA4_TRACKING` stays.
- `MESSENGER_TRANSPORT_DSN` is now honoured; it defaults to the database as before.
- the security scanner asks Packagist's security advisories API (GitHub Advisory Database plus
  FriendsOfPHP/security-advisories, what `composer audit` uses) instead of a git clone of
  FriendsOfPHP/security-advisories, so it needs to reach packagist.org instead of github.com. The clone in
  `var/security-advisories` is no longer read and can be deleted; the directory now only keeps the time of
  the last check. The first `repman:security:update-db` scans all packages again.

## UPGRADE FROM 1.1.x to 1.2.x

- symfony version has been raised to `5.2`, so if you have made any custom changes, we encourage you
  to read their upgrade guide [UPGRADE-5.2.md](https://github.com/symfony/symfony/blob/master/UPGRADE-5.2.md)
- clear cache (when using docker-compose.yml, more details in [#417](https://github.com/repman-io/repman/issues/417))

## UPGRADE FROM 1.0.x to 1.1.x

- no additional steps to follow

## UPGRADE FROM 0.6.0 to 1.0.x

- no additional steps to follow

## UPGRADE FROM 0.5.0 to 0.6.0

- if you want to reduce the space taken up by packages marked as unstable you can add a new command to the cron 
  `bin/console repman:package:clear-old-dists` (check ansible role `cron` for more details)
- symfony version has been raised to `5.1`, so if you have made any custom changes, we encourage you 
  to read their upgrade guide [UPGRADE-5.1.md](https://github.com/symfony/symfony/blob/master/UPGRADE-5.1.md)

## UPGRADE FROM 0.4.0 to 0.5.0

- metadata files now will be full json files (previously it was serialized php array)
- dist file will be saved only with reference in name (to reduce storage size)
- auto upgrade is handled using migrations: [#227](https://github.com/repman-io/repman/pull/227)

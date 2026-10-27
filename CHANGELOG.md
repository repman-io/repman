# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

On next release:
- [ ] check if upgrade info is required (UPGRADE.md)
- [ ] update src/Kernel.php (REPMAN_VERSION)
- [ ] update docker-compose.yml (image tags)

## [Unreleased]
### Security
- Update all dependencies; `composer audit` reported 88 advisories against the previous lock file
- Escape raw HTML in package READMEs (rendered unescaped on the package page)
- Do not let shared caches store repository authentication failures ([#613](https://github.com/repman-io/repman/issues/613))
- Docker: PHP 8.4, nginx 1.30, TLS 1.3, containers run as `www-data`, SSH accepts new host keys but refuses changed ones
- Docker: the build refuses PHP older than 8.4.27 (PHP2Shell, CVE-2026-109618 and CVE-2026-109619, remote code
  execution exploited in the wild) and turns session upload progress off (CVE-2026-115560, no fix yet)

### Changed
- Require PHP 8.4; Symfony 6.4 LTS, Doctrine ORM 3, Composer 2, Flysystem 3, CommonMark 2, PHPUnit 11, PHPStan 2
- Proxy Packagist through repo.packagist.org with conditional metadata refreshes ([#702](https://github.com/repman-io/repman/issues/702), [#709](https://github.com/repman-io/repman/issues/709))
- `repman:proxy:sync-releases` is deprecated and does nothing ([#701](https://github.com/repman-io/repman/issues/701))
- Honour `MESSENGER_TRANSPORT_DSN` ([#533](https://github.com/repman-io/repman/issues/533))
- Docker: the database image upgrades data of an older PostgreSQL (the 1.x releases shipped 11) to 17 in place;
  app waits for the database, consumer and cron for the migrations; own settings live in `.env.docker.local`
- `COMPOSER_AUTH` (e.g. a GitHub token against the API rate limit) is used when packages are synchronized
- Update front-end libraries (jQuery 3.7, Bootstrap 4.6, ApexCharts 3.54) with SRI
- Docker: Caddy replaces nginx. With `ACME_EMAIL` set it gets Let's Encrypt certificates for the app, the proxy and
  every organization's host by itself (issued on first use, only for hosts Repman serves, renewed in the
  background); otherwise it uses `docker/nginx/ssl/` or its own local CA. HTTP/3, no more openssl scripts
- Security scans use Packagist's security advisories API (GitHub Advisory Database plus FriendsOfPHP) instead of a
  git clone of FriendsOfPHP alone, which missed most advisories (e.g. 2 of 18 for league/commonmark)
- Downloads go through Symfony HttpClient (retries, redirects, concurrency) instead of ReactPHP and own retry code;
  clue/mq-react and react/* are no longer dependencies
- Bitbucket workspaces are listed through bitbucket/client instead of an own endpoint class; GitLab projects are
  fetched once instead of twice (owned projects are part of the membership listing)

### Fixed
- `repman:proxy:sync-metadata` never refreshed anything ([#328](https://github.com/repman-io/repman/issues/328))
- Artifact packages could not be downloaded ([#453](https://github.com/repman-io/repman/issues/453))
- Non UTF-8 READMEs failed the package sync ([#699](https://github.com/repman-io/repman/issues/699))
- Long `suggest` descriptions failed the package sync ([#563](https://github.com/repman-io/repman/pull/563))
- Disabled security scans still ran and sent e-mails ([#682](https://github.com/repman-io/repman/issues/682))
- Security scan of packages without a stable release always failed ([#443](https://github.com/repman-io/repman/issues/443))
- Pagination rendered an empty last page ([#318](https://github.com/repman-io/repman/issues/318))
- Empty package search hid unsynchronized packages ([#640](https://github.com/repman-io/repman/issues/640))
- GitHub organizations beyond the first 30 were ignored when adding packages
- Changed dependency constraints were not shown after a resync
- Old version dists were never cleaned up (`removeVersionDists` never matched any file)
- Download retries: no retry on client errors, backoff on 429/5xx, status read after redirects ([#577](https://github.com/repman-io/repman/issues/577))
- `TRUSTED_PROXIES` setups, download statistics and invitation links after the Symfony upgrade
- Plain Git, Mercurial and Subversion packages never showed versions, dependencies or dependants (they have no dist)
- Per-version statistics broke for versions with a slash (`dev-feature/x`) or organizations with "version" in the alias
- The timezone select showed (and saved) the first zone of the list for users still on the default UTC
- The Buddy button on the registration page appeared only when Bitbucket was enabled
- HTML in package descriptions was shown escaped, descriptions are plain text now
- Docker on Linux: `assets:install` could not write the bind mounted `public/` as `www-data`
- Docker: the database logged nothing, so a failing database restarted without a trace
- Per-version statistics listed normalized versions (3.0.2.0) instead of the package's own names
- Searching packages dropped the chosen sort order
- Security scans skipped packages installed from source (plain git over SSH, Mercurial, Subversion): they have no
  archive to read composer.lock from; the sync now keeps the committed lock of the scanned versions
- Artifact packages were never scanned (composer.lock at the archive root was not found)
- Path packages failed to synchronize: their dist, a directory on the server, was downloaded like an archive

### Removed
- PEAR package type (removed from Composer 2)
- Universal Analytics (`GA_TRACKING`)
- Abandoned dependencies: sensio/framework-extra-bundle, excelwebzone/recaptcha-bundle, buddy-works/buddy-works-php-api, buddy-works/oauth2-client, doctrine/annotations, doctrine/cache

## [1.3.4] - 2021-06-25
### Security
- Upgrade flysystem to 1.1.4 - fix [CVE-2021-32708](https://github.com/thephpleague/flysystem/security/advisories/GHSA-9f46-5r25-5wfm)

## [1.3.3] - 2021-05-31
### Fixed
- Fix package dependencies duplication ([#472](https://github.com/repman-io/repman/pull/472))

### Changed
- Remove Link entity from package read model ([#473](https://github.com/repman-io/repman/pull/473))
- Reduce sql query executions for organization token ([#474](https://github.com/repman-io/repman/pull/474))
- Update dependencies (dependabot updates)

## [1.3.2] - 2021-05-20
### Security
- Update Symfony to 5.2.9 - fix [CVE-2021-21424](https://symfony.com/blog/cve-2021-21424-prevent-user-enumeration-in-authentication-mechanisms)

## [1.3.1] - 2021-05-13
### Security
- Update Symfony to 5.2.8 - fix [CVE-2021-21424](https://symfony.com/blog/cve-2021-21424-prevent-user-enumeration-in-authentication-mechanisms)

### Changed
- Update dependencies ([#456](https://github.com/repman-io/repman/pull/456))

## [1.3.0] - 2021-04-28
### Security
- Update composer - fix [GHSA-h5h8-pc6h-jvvx](https://github.com/composer/composer/security/advisories/GHSA-h5h8-pc6h-jvvx)

### Added
- Dependency/dependant tracking ([#426](https://github.com/repman-io/repman/pull/426) thanks @giggsey)

## [1.2.2] - 2021-03-02
### Fixed
- Fix: composer 9999999-dev issue ([#422](https://github.com/repman-io/repman/pull/422) thanks @slappyslap)
- Make `var/cache` ephemeral ([#420](https://github.com/repman-io/repman/pull/420))
- Add async-aws/ses to composer ([#418](https://github.com/repman-io/repman/pull/418) thanks @nandogameiro)
- Enable http2 for composer v2  ([#416](https://github.com/repman-io/repman/pull/416))
- Test compatibility issues and small deprecation fix ([#414](https://github.com/repman-io/repman/pull/414) thanks @pedro-stanaka)
- Remove old metadata files when sync proxy metadata ([#412](https://github.com/repman-io/repman/pull/412))
- Remove PostgreSQL exposed port from docker-compose.yml ([#410](https://github.com/repman-io/repman/pull/410))

## [1.2.1] - 2021-02-03

### Fixed
- Remove webhook when package removed (API) and organization removed (UI) ([#404](https://github.com/repman-io/repman/pull/404))

## [1.2.0] - 2021-02-01

### Added
- Support for S3-compatible storage ([#332](https://github.com/repman-io/repman/pull/303), [#366](https://github.com/repman-io/repman/pull/366) thanks @pedro-stanaka)
- Cached adapters to reduce IO/HTTP overhead (storage) ([#373](https://github.com/repman-io/repman/pull/373) thanks @pedro-stanaka)
- Alternative domain separator option (to simplify working with certificates) ([#375](https://github.com/repman-io/repman/pull/375) thanks @jmalinens)
- Error messages for webhook actions (better UX) ([#396](https://github.com/repman-io/repman/pull/396))
- Adding support for self hosted gitlab on custom port ([#398](https://github.com/repman-io/repman/pull/398) thanks @Fahl-Design )

### Changed
- Improve organization invitation with registration/login flow ([#387](https://github.com/repman-io/repman/pull/387) thanks @noniagriconomie)
- Refresh oauth token in runtime without failing message or redirect ([#395](https://github.com/repman-io/repman/pull/395), [#397](https://github.com/repman-io/repman/pull/397))
- Upgrade Symfony to 5.2 ([#379](https://github.com/repman-io/repman/pull/379) and others from dependabot)
- Upgrade Doctrine and other dependencies (gitlab-api, github-api, bitbucket-api, dev tools)

## [1.1.1] - 2020-12-02

### Changed
- Direct docker cron logs to file ([#330](https://github.com/repman-io/repman/pull/330))

### Fixed
- Fix alias form constraint (regex) ([#326](https://github.com/repman-io/repman/pull/326))

## [1.1.0] - 2020-10-23

### Added
- Display README.md for packages ([#303](https://github.com/repman-io/repman/pull/303) thanks @giggsey)
- Allow package list to be sortable ([#300](https://github.com/repman-io/repman/pull/300) thanks @giggsey)
- Allow user to edit packages ([#299](https://github.com/repman-io/repman/pull/299))
- Improve Package Details UX ([#298](https://github.com/repman-io/repman/pull/298) thanks @giggsey)
- Implement user timezone ([#297](https://github.com/repman-io/repman/pull/297))
- Add option to limit number of package versions being imported ([#294](https://github.com/repman-io/repman/pull/294))

### Changed
- Repo JSON Performance Improvements ([#310](https://github.com/repman-io/repman/pull/310) thanks @giggsey)
- Update doctrine-bundle and symfony to remove deprecation notice ([#305](https://github.com/repman-io/repman/pull/305))

### Fixed
- Fix artifact repo security scan ([#315](https://github.com/repman-io/repman/pull/315) thanks @giggsey)
- Ensure that latest version is not removed when limit is applied ([#312](https://github.com/repman-io/repman/pull/312))
- Do not allow null values for number of last releases when updating ([#302](https://github.com/repman-io/repman/pull/302))

## [1.0.0] - 2020-09-29
### Added
- implement `provider-includes` for better proxy performance ([#281](https://github.com/repman-io/repman/pull/281), [#283](https://github.com/repman-io/repman/pull/283), [#290](https://github.com/repman-io/repman/pull/290))
- add version for assets ([#278](https://github.com/repman-io/repman/pull/278))
- add `reCaptcha` and better email validation ([#276](https://github.com/repman-io/repman/pull/276), [#277](https://github.com/repman-io/repman/pull/277))
- REST API implementation ([#269](https://github.com/repman-io/repman/pull/269), [#275](https://github.com/repman-io/repman/pull/275))
- add ability to search packages ([#259](https://github.com/repman-io/repman/pull/259), [#263](https://github.com/repman-io/repman/pull/263), thanks @giggsey)
- add `CODE_OF_CONDUCT.md` ([#258](https://github.com/repman-io/repman/pull/258))

### Changed
- remove `mailhog` from `docker-compose.yml` ([#293](https://github.com/repman-io/repman/pull/293))
- Tweak sysctl for better performance ([#265](https://github.com/repman-io/repman/pull/265), [#271](https://github.com/repman-io/repman/pull/271))

### Fixed
- Fix nginx and php-fpm to correct handle symlinks ([#262](https://github.com/repman-io/repman/pull/262))

## [0.6.0] - 2020-09-03
### Added
- implement command for clearing old private distributions files ([#244](https://github.com/repman-io/repman/pull/244))

### Security
- update symfony to 5.1.5 ([CVE-2020-15094](https://github.com/advisories/GHSA-754h-5r27-7x3r))

### Changed
- add queue for downloader to limit concurrent requests ([#253](https://github.com/repman-io/repman/pull/253))
- bump symfony to 5.1 ([#250](https://github.com/repman-io/repman/pull/250), thanks @marmichalski )
- atomic deployment with ansible playbook ([#241](https://github.com/repman-io/repman/pull/241), [#242](https://github.com/repman-io/repman/pull/242), [#243](https://github.com/repman-io/repman/pull/243), [#245](https://github.com/repman-io/repman/pull/245))
- set `ulimit -n` for system user ([#251](https://github.com/repman-io/repman/pull/251))

### Fixed
- fix Proxy response caching ([#247](https://github.com/repman-io/repman/pull/247), thanks @giggsey)

## [0.5.0] - 2020-08-04
### Changed
- higher memory limits ([#219](https://github.com/repman-io/repman/pull/219), [#220](https://github.com/repman-io/repman/pull/220))
- move all proxy logic to Proxy class ([#223](https://github.com/repman-io/repman/pull/223))
- use async and stream for downloading metadata and distributions files ([#226](https://github.com/repman-io/repman/pull/226))

### Added
- serve static proxy metadata and use v2 endpoint for dist lookup ([#222](https://github.com/repman-io/repman/pull/222))
- sync proxy metadata command ([#224](https://github.com/repman-io/repman/pull/224))
- migration for better auto upgrade to 0.5.0 ([#227](https://github.com/repman-io/repman/pull/227))
- static proxy with metadata cache ([#229](https://github.com/repman-io/repman/pull/229))
- cache headers for packages.json ([#232](https://github.com/repman-io/repman/pull/232))
- subversion client ([#230](https://github.com/repman-io/repman/pull/230), [#231](https://github.com/repman-io/repman/pull/231))
- create `.gitattributes` for better dist export ([#235](https://github.com/repman-io/repman/pull/235))
- telemetry ([#225](https://github.com/repman-io/repman/pull/225), [#234](https://github.com/repman-io/repman/pull/234))
- technical email ([#237](https://github.com/repman-io/repman/pull/237/files))

### Fixed
- migration syntax ([#236](https://github.com/repman-io/repman/pull/236))
- updating version date ([#238](https://github.com/repman-io/repman/pull/238), thanks @nickygerritsen)

## [0.4.1] - 2020-07-15
### Fixed
- Add support for IPv6 addresses ([#216](https://github.com/repman-io/repman/pull/216), thanks @nickygerritsen)
- Fix user voters with anonymous access ([#215](https://github.com/repman-io/repman/pull/215))

## [0.4.0] - 2020-07-13
### Added
- Registration config options ([#200](https://github.com/repman-io/repman/pull/200), thanks @nickygerritsen)
- Anonymous access to organization ([#201](https://github.com/repman-io/repman/pull/201))
- Basic support for Composer v2 ([#205](https://github.com/repman-io/repman/pull/205))
  - proxy support for metadata-url (thanks @sadortun)
  - repo support for metadata-url
- Package versions view ([#208](https://github.com/repman-io/repman/pull/208), thanks @nickygerritsen)

### Changed
- Unpack and update dependencies ([#204](https://github.com/repman-io/repman/pull/204))
- Containers restart policy ([#211](https://github.com/repman-io/repman/pull/211))

## [0.3.0] - 2020-06-05
### Added
- Security vulnerability scanner for private packages (#170, #171, #176, #177, #182, #183, #184, #190, #197)
- Sending scan results email to organization members (#194, #196)
- Allow user to disable account registration (#152)
- Create .htaccess (#163)
- Add repman:create:user cli command (#181)
- Add repman:package:synchronize cli command (#185, #186)

### Changed
- Hide oauth providers buttons when env var not configured (#167)
- Create user security read model - clean user domain (#188)
- Update symfony/mailer to 5.0.9 (#195)

### Fixed
- Fix GitLab custom instance url not being picked up by oauth client (#156)
- Use gitlab custom url in ComposerPackageSynchronizer (#162)
- Fix provider and dist removal (#168)
- Write custom Gitlab URL to gitlab-domains composer option (#179)

## [0.2.1] - 2020-05-07
### Security
- prevention of guessing package uuid for organization package endpoints (#148)

### Added
- package versions stats and tweak other charts (#145, #146)

### Changed
- Cleanup JS; Fix number of days in admin stats view; Force referrer in GA (#143, #144)
- handle package not found exception on app level (#142)
- tuning php-fpm configuration for better resources utilization (Ansible) (#141)
- add curl and pdo_pgsql to required php extensions (#140)

## [0.2.0] - 2020-05-05
### Added
- Organization members (#56)

### Changed
- Lock php version to 7.4.5 (Docker) (#131)

### Fixed
- Fix emails headers and match password requirements (#136)
- GitLab projects fetch - Add php curl extension to asible setup playbook (#133)
- Don't try to download packages without reference (#132)
- Fix database foreign keys (#127)
- Add autorestart flag to consumer configuration (Ansible supervisor) (#126)
- Return 404 when distribution file not found (#123)
- Add missing directories for docker instance (#117)

## [0.1.2] - 2020-04-27
### Added
- Add ability to unlink OAuth integration from user profile page (#106)
- Uptime Robot monitor (#102 & #103)

### Changes
- GitLab API: Show all user's packages and order by last activity (#104)

### Fixed
- Handle oauth errors during registration (#92)
- Handle errors when fetching repos from provider  (#94)
- Fix last package version detection mechanism (#99)
- Fix support for packages with slash in version name (#101)
- Fix number of days for /admin/stats (#108)
- Fix recent webhook requests view model (#110)
- Allow *.php named packages to be found (#111)

## [0.1.1] - 2020-04-22
### BC break
- user email is now change to lowercase with migration
    - if a user with the same e-mail registered in the application but with different character sizes then you will have to manually delete it before starting the migration

### Added
- Clickable repo url link on packages list (#75)

### Changed
- Use lock to prevent multiple jobs run simultaneously (#70)
- Internal CI/CD configuration

### Fixed
- Fix issue with case sensitive emails (#88)
- Typo on register form (#74)

### Removed
- Remove `pcov` from docker image  (#69)


## [0.1.0] - 2020-04-20
### First release :tada:
- free and open source
- works as a proxy for packagist.org (speeds up your local builds)
- hosts your private packages
- allows to create individual access tokens
- supports private package import from GitHub, GitLab and Bitbucket with one click

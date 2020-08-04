# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

On next release:
- [ ] update src/Kernel.php (REPMAN_VERSION)
- [ ] update docker-compose.yml (image tags)

## [Unreleased]

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

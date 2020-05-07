# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

On next release:
- [ ] update src/Kernel.php (REPMAN_VERSION)
- [ ] update docker-compose.yml (image tags)

## [Unreleased]

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

# Repman - PHP Repository Manager

Repman is a PHP repository manager. Main features:
 - work as proxy for packagist.org (speed up your local builds)
 - host your private packages
 - allow to create individual access tokens

## Requirements

 - PHP >= 7.4.1
 - `var` dir must be writeable
 - any web server

## Installation

```
git clone git@github.com:buddy-works/repman.git
cd repman
composer install
```

## Usage

Navigate your browser to instance address, you will see home page with usage instructions.

### CLI commands

 - `bin/console repman:metadata:clear-cache` - clear packages metadata cache (json files)


## Roadmap

 - [x] proxy for packagist.org
 - [x] repman composer plugin for seamless integration with existing projects
 - [x] local metadata cache
 - [ ] support for docker (to allow to create repman instance with docker)
 - [ ] admin panel
 - [ ] support private packages

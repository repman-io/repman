# UPGRADE

## UPGRADE FROM 0.5.0 to 0.6.0

- if you want to reduce the space taken up by packages marked as unstable you can add a new command to the cron 
  `bin/console repman:package:clear-old-dists` (check ansible role `cron` for more details)
- symfony version has been raised to `5.1`, so if you have made any custom changes, we encourage you 
  to read their upgrade guide [UPGRADE-5.1.md](https://github.com/symfony/symfony/blob/master/UPGRADE-5.1.md)

## UPGRADE FROM 0.4.0 to 0.5.0

- metadata files now will be full json files (previously it was serialized php array)
- dist file will be saved only with reference in name (to reduce storage size)

Auto upgrade is handled using migrations: [#227](https://github.com/repman-io/repman/pull/227)

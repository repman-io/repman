# UPGRADE

## UPGRADE FROM 0.4.0 to 0.5.0

- metadata files now will be full json files (previously it was serialized php array)
- dist file will be saved only with reference in name (to reduce storage size)

Auto upgrade is handled using migrations: [#227](https://github.com/repman-io/repman/pull/227)

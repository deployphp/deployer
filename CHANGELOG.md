# Changelog

## master
[master...v4.0.2](https://github.com/deployphp/deployer/compare/v4.0.2...master)

### Added
- Added `testLocally` function (analog `test` fn)
- Added `ConfigurationException`

### Changed
- Server config `setPty` renamed to `pty` [#953](https://github.com/deployphp/deployer/pull/953)
- Raised timeout for runLocally to 300 seconds [#955](https://github.com/deployphp/deployer/pull/955)
- `deploy:unlock` now always successful [#950](https://github.com/deployphp/deployer/pull/950)
- Added option `-L` to `setfacl` [#956](https://github.com/deployphp/deployer/pull/956)
- Run `deploy:unlock` when deploy is fail [#958](https://github.com/deployphp/deployer/pull/958)
- Now throw exception on duplicates in `shared_dirs`

### Fixed
- Fixed native ssh scp option
- Fixed bug with `$httpGroup` guard clause [#948](https://github.com/deployphp/deployer/pull/948)

## v4.0.2
🙄

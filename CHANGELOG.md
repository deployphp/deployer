# Changelog

## master
[v4.0.2...master](https://github.com/deployphp/deployer/compare/v4.0.2...master)

### Added
- Added `testLocally` function (analog `test` fn)
- Added `ConfigurationException`
- Show message on file download

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
[v4.0.1...v4.0.2](https://github.com/deployphp/deployer/compare/v4.0.1...v4.0.2)

### Fixed
- Fixed bug with copy shared files
- Fixed recursive upload in native ssh
- Improved Laravel recipe
- Improved exceptions in runLocally


## v4.0.1
[v4.0.0...v4.0.1](https://github.com/deployphp/deployer/compare/v4.0.0...v4.0.1)

### Added
- Added more writable modes

### Changed
- Allowed init command overriding
- Returned ACL as default writable mode

### Fixed
- Fixed SilverStripe recipe
- Fixed release sorting
- Fixed release cleanup
- Improved Symfony recipe
- Fixed `DotArray` syntax in `Collection`
- Fixed typo3 recipe
- Fixed remove of shared dir on first deploy


## v4.0.0
🙄

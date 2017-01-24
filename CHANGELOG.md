# Changelog

## master
[v4.1.0...master](https://github.com/deployphp/deployer/compare/v4.1.0...master)

### Changed
- `add()` now merges configuration options recursively [#962](https://github.com/deployphp/deployer/pull/962)
- Added `writable_chmod_recursive` boolean option to enable non-recursive `chmod`

## v4.1.0
[v4.0.2...v4.1.0](https://github.com/deployphp/deployer/compare/v4.0.2...v4.1.0)

### Added
- Added `testLocally` function (analog `test` fn)
- Added `ConfigurationException`
- Show message on file download
- Added support for multiplexing for NativeSsh [#918](https://github.com/deployphp/deployer/pull/918)
- Added GracefulShutdownException
- Added Magento2 recipe [#911](https://github.com/deployphp/deployer/pull/911)

### Changed
- Server config `setPty` renamed to `pty` [#953](https://github.com/deployphp/deployer/pull/953)
- Raised timeout for runLocally to 300 seconds [#955](https://github.com/deployphp/deployer/pull/955)
- `deploy:unlock` now always successful [#950](https://github.com/deployphp/deployer/pull/950)
- Added option `-L` to `setfacl` [#956](https://github.com/deployphp/deployer/pull/956)
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

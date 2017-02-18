# Changelog

## master
[v4.2.1...master](https://github.com/deployphp/deployer/compare/v4.2.1...master)

### Added
- Add a way to retrieve a defined task [#1008](https://github.com/deployphp/deployer/pull/1008)
- Add support for configFile in the NativeSsh implementation [#979](https://github.com/deployphp/deployer/pull/979)

### Changed
- Autoload functions via Composer [#1015](https://github.com/deployphp/deployer/pull/1015)

### Fixed
- Fixed `Can not share same dirs` for shared folders having similar names [#995](https://github.com/deployphp/deployer/issues/995)
- Fixed scalar override on recursive option merge [#1003](https://github.com/deployphp/deployer/pull/1003)
- Fixed incompatible PHP 7.0 syntax [#1020](https://github.com/deployphp/deployer/pull/1020)


### Changed
- Add task queue:restart for Laravel recipe [#1007](https://github.com/deployphp/deployer/pull/1007)

## v4.2.1
[v4.2.0...v4.2.1](https://github.com/deployphp/deployer/compare/v4.2.0...v4.2.1)

### Fixed
- Fixed `deployer/phar-update` dependency for composer installation.


## v4.2.0
[v4.1.0...v4.2.0](https://github.com/deployphp/deployer/compare/v4.1.0...v4.2.0)

### Added
- Added pretty print to config:dump command

### Changed
- `add()` now merges configuration options recursively [#962](https://github.com/deployphp/deployer/pull/962)
- Added `writable_chmod_recursive` boolean option to enable non-recursive `chmod`
- `ask()` now supports autocomplete [#978](https://github.com/deployphp/deployer/pull/978)
- `release_path` returns `current_path` in non-deploy context [#922](https://github.com/deployphp/deployer/pull/922)

### Fixed
- Fixed Flow recipe [#986](https://github.com/deployphp/deployer/pull/986)
- Fixed `deploy:copy_dirs` task [#914](https://github.com/deployphp/deployer/pull/914)
- Fixed default behavior for `working_path` [#381](https://github.com/deployphp/deployer/pull/381)

### Removed
- Removed const `Environment::DEPLOY_PATH`


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

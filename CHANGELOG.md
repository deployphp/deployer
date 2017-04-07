# Changelog

## master
[v5.0.0-beta.1...master](https://github.com/deployphp/deployer/compare/v5.0.0-beta.1...master)

### Added
- Added console init template for Yii2 basic and advanced receipe [#1146](https://github.com/deployphp/deployer/pull/1146)
- Added `artisan:storage:link` task to the Laravel recipe to symlink the public storage directory [#1152](https://github.com/deployphp/deployer/pull/1152)

### Changed
- Error message on locked release [#1145](https://github.com/deployphp/deployer/pull/1145)

### Fixed
- Fixed task order init/shared for yii2-app-advanced.php [#1143](https://github.com/deployphp/deployer/pull/1143)


## v5.0.0-beta.1
[v4.3.0...v5.0.0-beta.1](https://github.com/deployphp/deployer/compare/v4.3.0...v5.0.0-beta.1)

### Added
- Added `use_atomic_symlink` and `use_relative_symlink` option [14a8f8](https://github.com/deployphp/deployer/pull/1092/commits/14a8f8f9c4ebbc7da45c2b6b7c3c00a51b563ccf)
- Added `Ssh\Client` [#1092](https://github.com/deployphp/deployer/pull/1092)
- Added host ranges [#1092](https://github.com/deployphp/deployer/pull/1092)
- Added --hosts and --roles options [#1092](https://github.com/deployphp/deployer/pull/1092)
- Added `on` function [#1092](https://github.com/deployphp/deployer/pull/1092)
- Added `host` and `localhost` [#1092](https://github.com/deployphp/deployer/pull/1092)
- Added persistent config [#1092](https://github.com/deployphp/deployer/pull/1092)
- Added `--log` option [#1092](https://github.com/deployphp/deployer/pull/1092)
- Added `cleanup_use_sudo` [#330](https://github.com/deployphp/deployer/pull/330)

### Changed
- `server` refactored to `host` [#1092](https://github.com/deployphp/deployer/pull/1092)
- `Enviroment` refactored to `Configuration` [#1092](https://github.com/deployphp/deployer/pull/1092)
- phpunit test refactored [#1092](https://github.com/deployphp/deployer/pull/1092)
- `upload` and `download` now uses rsync [#1092](https://github.com/deployphp/deployer/pull/1092)
- Only native ssh client for now [#1092](https://github.com/deployphp/deployer/pull/1092)
- Task `current` to `config:current` [#1092](https://github.com/deployphp/deployer/pull/1092)
- `onFailure` to `fail` [#1092](https://github.com/deployphp/deployer/pull/1092)


## v4.3.0
[v4.2.1...v4.3.0](https://github.com/deployphp/deployer/compare/v4.2.1...v4.3.0)

### Added
- Added support for multiple choice questions [#1076](https://github.com/deployphp/deployer/pull/1076)
- Added a way to retrieve a defined task [#1008](https://github.com/deployphp/deployer/pull/1008)
- Added support for configFile in the NativeSsh implementation [#979](https://github.com/deployphp/deployer/pull/979)
- Added `--no-hooks` option for running commands without `before()` and `after()` [#1061](https://github.com/deployphp/deployer/pull/1061)
- Added a usefull error when ask*() is not used wihtin a task() [#1083](https://github.com/deployphp/deployer/pull/1083)

### Changed
- Parse hyphens in environment setting names [#1073](https://github.com/deployphp/deployer/pull/1074)
- Autoload functions via Composer [#1015](https://github.com/deployphp/deployer/pull/1015)
- Added task queue:restart for Laravel recipe [#1007](https://github.com/deployphp/deployer/pull/1007)
- Changed output of errors for native ssh [#1012](https://github.com/deployphp/deployer/issues/1012)

### Fixed
- Fixed `Can not share same dirs` for shared folders having similar names [#995](https://github.com/deployphp/deployer/issues/995)
- Fixed scalar override on recursive option merge [#1003](https://github.com/deployphp/deployer/pull/1003)
- Fixed incompatible PHP 7.0 syntax [#1020](https://github.com/deployphp/deployer/pull/1020)
- Fixed an issue with the output of ls in releases_list [#1004](https://github.com/deployphp/deployer/issues/1004) [#1036](https://github.com/deployphp/deployer/pull/1036/)
- Fixed possibility to use PEM files with Native SSH
- Fixed old releases not being cleaned up when keep_releases reduced by more than half.
- Fixed creating non-existed `writable_dirs` [#1000](https://github.com/deployphp/deployer/pull/1000)
- Fixed uploading files with spaces in a path via Native SSH [#1010](https://github.com/deployphp/deployer/issues/1010)
- Fixed merge of string array config options [#1067](https://github.com/deployphp/deployer/pull/1067)
- Fixed uploading of files containing spaces [#1077](https://github.com/deployphp/deployer/issues/1077)
- Fixed download of files when filename remote contains spaces [#1082](https://github.com/deployphp/deployer/pull/1082)

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

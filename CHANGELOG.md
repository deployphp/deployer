# Changelog

## master
[v5.0.3...master](https://github.com/deployphp/deployer/compare/v5.0.3...master)

### Added
- Check what `unzip` exists in `deploy:vendors` task
- Added `dep run` command [#1263]
- Added new `-o` option which allow to override default configuration
- Added `dep autocomplete` command

### Changed
- Use either one of `command`, `which` or `type` commands to locate custom binary path.

### Fixed
- Fixed parallel execution with non-standart php bin path [#1265]
- Fixed ssh multiplexing initialization [#1268]
- Fixed exit code on error [#1236]
- Fixed bug with deploying in parallel to same host [#1271]

## v5.0.3
[v5.0.2...v5.0.3](https://github.com/deployphp/deployer/compare/v5.0.2...v5.0.3)

### Fixed
- Fix a parsing of laravel version in output [#1252]


## v5.0.2
[v5.0.1...v5.0.2](https://github.com/deployphp/deployer/compare/v5.0.1...v5.0.2)

### Added
- Added `laravel_version` param [#1246]

### Fixed
- Fixed upload / download with optional rsync ssh options [#1227]
- Disable maintenance mode when Magento2 deployment fails [#1251]
- Fixed storage link error when deploying Laravel < 5.3 [#1246]


## v5.0.1
[v5.0.0...v5.0.1](https://github.com/deployphp/deployer/compare/v5.0.0...v5.0.1)

### Added
- Exception when no task will be executed
- Check for php7 in phar

### Fixed
- Throw the correct exception on git --reference fail
- Check if multiplexing is working before continuing [#1192]
- Fixed upload with non-standard SSH port [#1218]
- Ensure that host roles are treated as an array.


## v5.0.0
[v5.0.0-beta.3...v5.0.0](https://github.com/deployphp/deployer/compare/v5.0.0-beta.3...v5.0.0)

### Changed
- Working path default is `release_path` instead of home for simple tasks [#1205]

### Fixed
- Fixed ssh multiplexing master connection initializing
- Fixed `dep ssh` command [#1204]
- Fixed `dep config:current` task

## v5.0.0-beta.3
[v5.0.0-beta.2...v5.0.0-beta.3](https://github.com/deployphp/deployer/compare/v5.0.0-beta.2...v5.0.0-beta.3)

### Added
- Added `Request` class for get/post json requests
- Added host's `addSshFlag` and `addSshOption` methods

### Changed
- Allow to configure multiplexing [#1165]

### Fixed
- Fixed command parsing in runLocally func
- Fixed releases list and cleanup task [#1175]

## v5.0.0-beta.2
[v5.0.0-beta.1...v5.0.0-beta.2](https://github.com/deployphp/deployer/compare/v5.0.0-beta.1...v5.0.0-beta.2)

### Added
- Added console init template for Yii2 basic and advanced receipe [#1146]
- Added `artisan:storage:link` task to the Laravel recipe to symlink the public storage directory [#1152]
- Added `previous_release` var

### Changed
- Error message on locked release [#1145]

### Fixed
- Fixed task order init/shared for yii2-app-advanced.php [#1143]


## v5.0.0-beta.1
[v4.3.0...v5.0.0-beta.1](https://github.com/deployphp/deployer/compare/v4.3.0...v5.0.0-beta.1)

### Added
- Added `use_atomic_symlink` and `use_relative_symlink` option [14a8f8](https://github.com/deployphp/deployer/pull/1092/commits/14a8f8f9c4ebbc7da45c2b6b7c3c00a51b563ccf)
- Added `Ssh\Client` [#1092]
- Added host ranges [#1092]
- Added --hosts and --roles options [#1092]
- Added `on` function [#1092]
- Added `host` and `localhost` [#1092]
- Added persistent config [#1092]
- Added `--log` option [#1092]
- Added `cleanup_use_sudo` [#330]

### Changed
- `server` refactored to `host` [#1092]
- `Enviroment` refactored to `Configuration` [#1092]
- phpunit test refactored [#1092]
- `upload` and `download` now uses rsync [#1092]
- Only native ssh client for now [#1092]
- Task `current` to `config:current` [#1092]
- `onFailure` to `fail` [#1092]


## v4.3.0
[v4.2.1...v4.3.0](https://github.com/deployphp/deployer/compare/v4.2.1...v4.3.0)

### Added
- Added support for multiple choice questions [#1076]
- Added a way to retrieve a defined task [#1008]
- Added support for configFile in the NativeSsh implementation [#979]
- Added `--no-hooks` option for running commands without `before()` and `after()` [#1061]
- Added a usefull error when ask*() is not used wihtin a task() [#1083]

### Changed
- Parse hyphens in environment setting names [#1073]
- Autoload functions via Composer [#1015]
- Added task queue:restart for Laravel recipe [#1007]
- Changed output of errors for native ssh [#1012]

### Fixed
- Fixed `Can not share same dirs` for shared folders having similar names [#995]
- Fixed scalar override on recursive option merge [#1003]
- Fixed incompatible PHP 7.0 syntax [#1020]
- Fixed an issue with the output of ls in releases_list [#1004] [#1036]
- Fixed possibility to use PEM files with Native SSH
- Fixed old releases not being cleaned up when keep_releases reduced by more than half.
- Fixed creating non-existed `writable_dirs` [#1000]
- Fixed uploading files with spaces in a path via Native SSH [#1010]
- Fixed merge of string array config options [#1067]
- Fixed uploading of files containing spaces [#1077]
- Fixed download of files when filename remote contains spaces [#1082]

## v4.2.1
[v4.2.0...v4.2.1](https://github.com/deployphp/deployer/compare/v4.2.0...v4.2.1)

### Fixed
- Fixed `deployer/phar-update` dependency for composer installation.


## v4.2.0
[v4.1.0...v4.2.0](https://github.com/deployphp/deployer/compare/v4.1.0...v4.2.0)

### Added
- Added pretty print to config:dump command

### Changed
- `add()` now merges configuration options recursively [#962]
- Added `writable_chmod_recursive` boolean option to enable non-recursive `chmod`
- `ask()` now supports autocomplete [#978]
- `release_path` returns `current_path` in non-deploy context [#922]

### Fixed
- Fixed Flow recipe [#986]
- Fixed `deploy:copy_dirs` task [#914]
- Fixed default behavior for `working_path` [#381]

### Removed
- Removed const `Environment::DEPLOY_PATH`


## v4.1.0
[v4.0.2...v4.1.0](https://github.com/deployphp/deployer/compare/v4.0.2...v4.1.0)

### Added
- Added `testLocally` function (analog `test` fn)
- Added `ConfigurationException`
- Show message on file download
- Added support for multiplexing for NativeSsh [#918]
- Added GracefulShutdownException
- Added Magento2 recipe [#911]

### Changed
- Server config `setPty` renamed to `pty` [#953]
- Raised timeout for runLocally to 300 seconds [#955]
- `deploy:unlock` now always successful [#950]
- Added option `-L` to `setfacl` [#956]
- Now throw exception on duplicates in `shared_dirs`

### Fixed
- Fixed native ssh scp option
- Fixed bug with `$httpGroup` guard clause [#948]



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

[#1271]: https://github.com/deployphp/deployer/pull/1271
[#1268]: https://github.com/deployphp/deployer/pull/1268
[#1265]: https://github.com/deployphp/deployer/pull/1265
[#1263]: https://github.com/deployphp/deployer/pull/1263
[#1252]: https://github.com/deployphp/deployer/pull/1252
[#1251]: https://github.com/deployphp/deployer/pull/1251
[#1246]: https://github.com/deployphp/deployer/pull/1246
[#1236]: https://github.com/deployphp/deployer/issues/1236
[#1227]: https://github.com/deployphp/deployer/pull/1227
[#1218]: https://github.com/deployphp/deployer/issues/1218
[#1205]: https://github.com/deployphp/deployer/issues/1205
[#1204]: https://github.com/deployphp/deployer/issues/1204
[#1192]: https://github.com/deployphp/deployer/issues/1192
[#1175]: https://github.com/deployphp/deployer/pull/1175
[#1165]: https://github.com/deployphp/deployer/issues/1165
[#1153]: https://github.com/deployphp/deployer/issues/1153
[#1152]: https://github.com/deployphp/deployer/pull/1152
[#1146]: https://github.com/deployphp/deployer/pull/1146
[#1145]: https://github.com/deployphp/deployer/pull/1145
[#1143]: https://github.com/deployphp/deployer/pull/1143
[#1092]: https://github.com/deployphp/deployer/pull/1092
[#1083]: https://github.com/deployphp/deployer/pull/1083
[#1082]: https://github.com/deployphp/deployer/pull/1082
[#1077]: https://github.com/deployphp/deployer/issues/1077
[#1076]: https://github.com/deployphp/deployer/pull/1076
[#1073]: https://github.com/deployphp/deployer/pull/1073
[#1067]: https://github.com/deployphp/deployer/pull/1067
[#1061]: https://github.com/deployphp/deployer/pull/1061
[#1036]: https://github.com/deployphp/deployer/pull/1036
[#1020]: https://github.com/deployphp/deployer/pull/1020
[#1015]: https://github.com/deployphp/deployer/pull/1015
[#1012]: https://github.com/deployphp/deployer/issues/1012
[#1010]: https://github.com/deployphp/deployer/issues/1010
[#1008]: https://github.com/deployphp/deployer/pull/1008
[#1007]: https://github.com/deployphp/deployer/pull/1007
[#1004]: https://github.com/deployphp/deployer/issues/1004
[#1003]: https://github.com/deployphp/deployer/pull/1003
[#1000]: https://github.com/deployphp/deployer/pull/1000
[#995]: https://github.com/deployphp/deployer/issues/995
[#986]: https://github.com/deployphp/deployer/pull/986
[#979]: https://github.com/deployphp/deployer/pull/979
[#978]: https://github.com/deployphp/deployer/pull/978
[#962]: https://github.com/deployphp/deployer/pull/962
[#956]: https://github.com/deployphp/deployer/pull/956
[#955]: https://github.com/deployphp/deployer/pull/955
[#953]: https://github.com/deployphp/deployer/pull/953
[#950]: https://github.com/deployphp/deployer/pull/950
[#948]: https://github.com/deployphp/deployer/pull/948
[#922]: https://github.com/deployphp/deployer/pull/922
[#918]: https://github.com/deployphp/deployer/pull/918
[#914]: https://github.com/deployphp/deployer/pull/914
[#911]: https://github.com/deployphp/deployer/pull/911
[#381]: https://github.com/deployphp/deployer/pull/381
[#330]: https://github.com/deployphp/deployer/pull/330

# Changelog

## master

### Added
- Added cache clear/warmup task for symfony4 recipe [#1575]
- Added ability to use config params in host variables [#1508]
- Make used shell configurable via `shellCommand` [#1536]
- Added `cleanup_tty` option for `deploy:cleanup`
- Added Prestashop 1.6 recipe

### Fixed
- Fixed that long http user name is not detected correctly [#1580]
- Fixed missing `var/sessions` in Symfony 4 shared_dirs
- Fixed warning with host without configuration [#1583]
- Removed the `magento:enable` task from the Magento 2 recipe since the module states are defined in `app/etc/config.php` and this task overwrote that.
- Allow to set template file path in Drupal 7 recipe [#1603]
- Fixed once() tasks that where being run multiple times with ParallelExecutor

## v6.1.0
[v6.0.5...v6.1.0](https://github.com/deployphp/deployer/compare/v6.0.5...v6.1.0)

### Added
- Added debug:task command to display the order of task execution [#1488]
- Added a description to the autocomplete command [#1472]
- Added logging of unhandled exceptions into logfile [#1481]
- Added default -H flag when using become [#1556]
- Added Symfony 4 recipe [#1437]

### Fixed
- Fixed within() to also restore the working-path when the given callback throws a Exception [#1463]
- Fixed `pcntl_fork` check for blacklisted Ubuntu LTS boxes [#1476]
- Fixed shared dir/file paths containing variables (`{{variable}}`)

### Changed
- Throw meaningfull exception on errors in cd() [#1480]
- Make sure Context::pop() is called when Callback errors in on(...) function [#1513]
- Update silverstripe recipe to support silverstripe 4
- Show standard output in exceptions when error output is empty [#1554]
- Improve readability of command for finding web server user [#1557]
- Update symfony package dependencies to ~4.0 [#1559]


## v6.0.5
[v6.0.4...v6.0.5](https://github.com/deployphp/deployer/compare/v6.0.4...v6.0.5)

### Fixed
- Fixed `previous_release` param when `release_name` was overridden [#1455]


## v6.0.4
[v6.0.3...v6.0.4](https://github.com/deployphp/deployer/compare/v6.0.3...v6.0.4)

### Changed
- Added support for GroupTask in invoke() [#1364]
- Magento2 recipe optimizes the autoloader after the DI compilation [#1365]
- Host's `roles()` API now can accept arrays too
- Fixed bug where wrong time format is passed to touch when deploying assets [#1390]
- Added artisan:migrate:fresh task for laravel recipe
- Added platform config to composer.json [#1426]
- Moved symfony finder to dev-dependency [#1452]

### Fixed
- Fixed bug when config:hosts shows more than one table of hosts [#1403]
- Fixed bug that inventory method does not return Proxy [#1413]


## v6.0.3
[v6.0.2...v6.0.3](https://github.com/deployphp/deployer/compare/v6.0.2...v6.0.3)

### Changed
- Laravel version check defaults to 5.5 if not found [#1352]

### Fixed
- Updated Laravel recipe to not run `artisan:optimize` on Laravel >= 5.5, as that command is now deprecated ([see upgrade notes](https://laravel.com/docs/5.5/upgrade)) [#1352]


## v6.0.2
[v6.0.1...v6.0.2](https://github.com/deployphp/deployer/compare/v6.0.1...v6.0.2)

### Fixed
- Fixed bug with curl ssh check in _Httpie_ util


## v6.0.1
[v6.0.0...v6.0.1](https://github.com/deployphp/deployer/compare/v6.0.0...v6.0.1)

### Fixed
- Fixed stat url


## v6.0.0
[v5.1.3...v6.0.0](https://github.com/deployphp/deployer/compare/v5.1.3...v6.0.0)

### Added
- Added possibility to use callable when setting 'default_stage'
- Added console init template for TYPO3 CMS [#1300]
- Added possibility to run a task only once [#1311]
- Added `git_recursive` option
- Added `shallow` task option
- Added `deploy:info` task
- Added `writable_tty` option for `deploy:writable`
- Added `default_timeout` option [#1256]
- Added `user` parameter

### Changed
- Changed `branch` parameter and option behavior
- Extended `task` func to support callables
- Renamed `env_vars` to `env`

### Fixed
- Improved the way `ParallelExecutor` handles option parameters
- Fixed no `stage` argument in parallel mode [#1299]
- Improved environment variables management
- Fixed `runLocally` to not cd into remote dir

### Removed
- Removed `terminate_message` option
- Removed `Result` class


## v5.1.3
[v5.1.2...v5.1.3](https://github.com/deployphp/deployer/compare/v5.1.2...v5.1.3)

### Fixed
- Fixed bug with wrong version printed after self-update command
- Fixed bug with excess option "--no-debug" in deploy:cache:clear task [#1290]


## v5.1.2
[v5.1.1...v5.1.2](https://github.com/deployphp/deployer/compare/v5.1.1...v5.1.2)

### Changed
- Improved `config:current` output (print each host's current release)
- Fixed cache clearing in the Symfony recipe (now runs both cache:clear and cache:warmup) [#1283]

### Fixed
- Fixed bug where `ParallelExecutor` threw an error when custom options were added
- Fixed bug with parallel deploy in multi user envirouments [#1269]


## v5.1.1
[v5.1.0...v5.1.1](https://github.com/deployphp/deployer/compare/v5.1.0...v5.1.1)

### Fixed
- Fixed bug with `self-update` warnings [#1226]


## v5.1.0
[v5.0.3...v5.1.0](https://github.com/deployphp/deployer/compare/v5.0.3...v5.1.0)

### Added
- Check what `unzip` exists in `deploy:vendors` task
- Added `dep run` command [#1263]
- Added new `-o` option which allow to override default configuration
- Added `dep autocomplete` command
- Added `dep config:hosts` task to show inventory

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

[#1603]: https://github.com/deployphp/deployer/issues/1603
[#1583]: https://github.com/deployphp/deployer/issues/1583
[#1580]: https://github.com/deployphp/deployer/pull/1580
[#1575]: https://github.com/deployphp/deployer/pull/1575
[#1559]: https://github.com/deployphp/deployer/pull/1559
[#1557]: https://github.com/deployphp/deployer/pull/1557
[#1556]: https://github.com/deployphp/deployer/pull/1556
[#1554]: https://github.com/deployphp/deployer/pull/1554
[#1536]: https://github.com/deployphp/deployer/pull/1536
[#1521]: https://github.com/deployphp/deployer/pull/1521
[#1513]: https://github.com/deployphp/deployer/pull/1513
[#1508]: https://github.com/deployphp/deployer/issues/1508
[#1488]: https://github.com/deployphp/deployer/issues/1488
[#1481]: https://github.com/deployphp/deployer/issues/1481
[#1480]: https://github.com/deployphp/deployer/issues/1480
[#1476]: https://github.com/deployphp/deployer/pull/1476
[#1472]: https://github.com/deployphp/deployer/pull/1472
[#1463]: https://github.com/deployphp/deployer/pull/1463
[#1455]: https://github.com/deployphp/deployer/pull/1455
[#1452]: https://github.com/deployphp/deployer/pull/1452
[#1437]: https://github.com/deployphp/deployer/issues/1437
[#1426]: https://github.com/deployphp/deployer/pull/1426
[#1419]: https://github.com/deployphp/deployer/issues/1419
[#1413]: https://github.com/deployphp/deployer/pull/1413
[#1403]: https://github.com/deployphp/deployer/pull/1403
[#1390]: https://github.com/deployphp/deployer/pull/1390
[#1365]: https://github.com/deployphp/deployer/pull/1365
[#1364]: https://github.com/deployphp/deployer/pull/1364
[#1352]: https://github.com/deployphp/deployer/pull/1352
[#1311]: https://github.com/deployphp/deployer/pull/1311
[#1300]: https://github.com/deployphp/deployer/pull/1300
[#1299]: https://github.com/deployphp/deployer/issues/1299
[#1290]: https://github.com/deployphp/deployer/pull/1290
[#1283]: https://github.com/deployphp/deployer/pull/1283
[#1271]: https://github.com/deployphp/deployer/pull/1271
[#1269]: https://github.com/deployphp/deployer/pull/1269
[#1268]: https://github.com/deployphp/deployer/pull/1268
[#1265]: https://github.com/deployphp/deployer/pull/1265
[#1263]: https://github.com/deployphp/deployer/pull/1263
[#1256]: https://github.com/deployphp/deployer/issues/1256
[#1252]: https://github.com/deployphp/deployer/pull/1252
[#1251]: https://github.com/deployphp/deployer/pull/1251
[#1246]: https://github.com/deployphp/deployer/pull/1246
[#1236]: https://github.com/deployphp/deployer/issues/1236
[#1227]: https://github.com/deployphp/deployer/pull/1227
[#1226]: https://github.com/deployphp/deployer/issues/1226
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

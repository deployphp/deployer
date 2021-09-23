# Changelog


## master
[v6.8.0...master](https://github.com/deployphp/deployer/compare/v6.8.0...master)

### Added
- Added documentation generation from recipes.
- Added support for `ask()` functions in parallel mode.
- Added support for `sudo` commands.
- Added `dep status` command which shows currently deployed revisions.
- Added `dep hosts` command to show hosts info in json format.
- Added `dep tree` command to show task configuration.
- Added `--plan` option to show execution plan, which tasks on which hosts.
- Added `dep push` command to quickly push local changes to remote hosts.
- Added yaml recipe syntax.
- Added dotenv support on remote hosts.
- Added labels and selectors support.
- Added support for placeholders in `run()` func.
- Added support for secret passing in `run()` func without outputting to logs.
- Added docker-based E2E testing environment. [#2197]
- Added support for PHP8.
- Added slack_channel option to Slack recipe.
- Chatwork contrib recipe.
- Added `release_or_current_path` option that fallbacks to the `current_path` when the `release_path` does not exist. [#2486]
- Added `contrib/php-fpm.php` recipe that provides a task to reload PHP-fpm. [#2487]
- Added tasks `artisan:key:generate` and `artisan:passport:keys` to the Laravel recipe.
- Added the following artisan tasks: `artisan:route:clear`, `artisan:route:list`, `artisan:horizon`, `artisan:horizon:clear`, `artisan:horizon:continue`, `artisan:horizon:list`, `artisan:horizon:pause`, `artisan:horizon:purge`, `artisan:horizon:status`, `artisan:event:list`, `artisan:queue:failed`, `artisan:queue:flushed`. [#2488]
- Isolated console application runner for E2E tests.
- Support for code coverage in E2E tests.
- Webpack-encore contrib recipe.
- Recipe for Statamic.

### Changed
- Refactored executor engine, up to 2x faster than before.
- Refactored `common.php` recipe to support new frameworks.
- Refactored bash/zsh autocomplete to support hosts.
- Docs rewritten to be more clean, easy to use and understandable.
- Better parallel execution support, configurable per task.
- Refactored `dep init` command.
- Normalize shopware recipe (require common.php).
- Removed the `min` and `max` constraints on the `artisan:optimize` and `artisan:optimize:clear` tasks. [#2488]
- Excluded the `shared_files`, `shared_dirs` and `writable_dirs` configs from the `deploy.yaml` default template unless the `common` template was chosen.
- The way `deploy:update_code` fetched the GIT remote URL from the config.
- Use cp instead of rsync when copying directories. [#2656]

### Fixed
- Lots, and lots of long-standing bugs.
- Shopware recipe plugin active/update.
- Fixed incorrect plugin:list parsing (remove duplicate version column). Invoke nested sw:plugin:refresh task instead of redefining it, so that it actually runs.
- Shopware activates/runs migration in order (respects dependencies in composer.json). [#2423] [#2425]
- Boolean options should not go through the `self::escape` function. [#2392]
- Check if shared file exists before touching it (and fail because no write permission). [#2393]
- Fixed "dep run" suggestion in ACL error message. [#2501]
- TypeError, port is int on escapeshellarg. [#2503]
- .env.local.php in Symfony recipe. [#2506]
- Fixed regex identifying "cd" commands in YAML scripts. The regex was not taking into account that multiple commands can be executed within the same line — e.g. "cd {{release_path}} && npm run prod". [#2509]
- Slack default channel null breaks webhook. [#2525]
- Use port for ssh-keyscan when applicable. [#2549]
- Support passing `null` as default in get(). [#2545]
- Shopware recipe: First activate plugins THEN build them to avoid breaking theme:compile with unbuild themes (which were activated AFTER build).
- Shopware recipe sw:plugin:upgrade:all task.
- Only perform keyscan for repos that are pulled over ssh. Better detection of hostname. [#2667]

### Removed
- Removed the `artisan:public_disk` task. Use the `artisan:storage:link` task instead. [#2488]
- Removed the following tasks `artisan:horizon:assets`, `artisan:horizon:publish`, `artisan:telescope:publish` and `artisan:nova:publish`. These commands publish code that should be commited. [#2488]


## v6.8.0
[v6.7.3...v6.8.0](https://github.com/deployphp/deployer/compare/v6.7.3...v6.8.0)

### Added
- Documented check_remote task usage.
- Speedup deploy:clear_paths.
- Documented default_timeout config usage.
- Recipe for Joomla!.

### Fixed
- Fixed Silverstripe CMS recipe assets path. [#1989]
- Fixed check_remote task errors. [#1990]
- Fixed check_remote task revision resolution. [#1994]
- Fixed backward compatibility of bin/console for symfony4 recipe.
- Keep consistency with executable naming in lock recipe.
- Unexpected exception in config:* tasks when no stage is defined for host. [#1909] [#1909] [#1909]
- Fixed parsing of installed.json by Composer version 2.
- Fixed only call bin/php on the composer.phar file.


## v6.7.3
[v6.7.2...v6.7.3](https://github.com/deployphp/deployer/compare/v6.7.2...v6.7.3)

### Fixed
- Fixed more Symfony 5 compatibility issues. [#1971]


## v6.7.2
[v6.7.1...v6.7.2](https://github.com/deployphp/deployer/compare/v6.7.1...v6.7.2)

### Fixed
- Fixed compatibility with Symfony 4.x.


## v6.7.1
[v6.7.0...v6.7.1](https://github.com/deployphp/deployer/compare/v6.7.0...v6.7.1)

### Fixed
- Fixed incompatibility with Symfony 5. [#1969]


## v6.7.0
[v6.6.0...v6.7.0](https://github.com/deployphp/deployer/compare/v6.6.0...v6.7.0)

### Changed
- Added Symfony 5 support.


## v6.6.0
[v6.5.0...v6.6.0](https://github.com/deployphp/deployer/compare/v6.5.0...v6.6.0)

### Added
- Added doc page with sample "real-world" Deployer script.

### Fixed
- Parameters `-f` or `--file` now are accepted also without the equal sign. [#1479]


## v6.5.0
[v6.4.7...v6.5.0](https://github.com/deployphp/deployer/compare/v6.4.7...v6.5.0)

### Added
- Added `deploy:check_remote` task. [#1755]


## v6.4.7
[v6.4.6...v6.4.7](https://github.com/deployphp/deployer/compare/v6.4.6...v6.4.7)

### Added
- A task to cache the event listeners manifest in Laravel. [#1893]
- Added `check_remote_head` option, by setting this to true, deployer will avoid unnecessary new releases by checking the remote git HEAD without cloning the repo. [#1755]

### Fixed
- Fixed invalid magic-property phpdoc in Deployer\Deployer class. [#1899]
- Updated `config:hosts` and `config:current` tasks to output only the selected stage.


## v6.4.6
[v6.4.5...v6.4.6](https://github.com/deployphp/deployer/compare/v6.4.5...v6.4.6)

### Added
- Re-added the `artisan:view:clear` task.

### Changed
- Change the default shared files in the Symfony4 recipe. The .env file is versionned now and not the .env.local. [#1881]
- Change the `artisan:view:cache` task to only run the `view:cache` command.


## v6.4.5
[v6.4.4...v6.4.5](https://github.com/deployphp/deployer/compare/v6.4.4...v6.4.5)

### Fixed
- Fixed detection of http user. [#1876]


## v6.4.4
[v6.4.3...v6.4.4](https://github.com/deployphp/deployer/compare/v6.4.3...v6.4.4)

### Added
- Added `git_clone_dissociate` option, defaults to true; when set to false git-clone doesn't dissociate the eventual reference repository after clone, useful when using git-lfs. [#1820]
- Added `writable_recursive` option (default: true) used in all writable modes (chmod, chown, chgrp, acl). [#1822]
- Added `artisan:optimize:clear` task for Laravel 5.7 and above.

### Changed
- Added lock and unlock task to flow_framework receipe.
- Updated `artisan:optimize` to run for Laravel 5.7 and above, since [it got added back](https://github.com/laravel/framework/commit/fe1cbdf3b51ce1235b8c91f5e603f1e9306e4f6f) last year. It still doesn't run for 5.5 and below.
- View:clear command to a new view:cache command.

### Fixed
- Fixed rsync upload honor become option for host. [#1796]
- Fixed bug to execute ssh command on windows. [#1775]
- Fixed when recipe/deploy/writable.php resolves <defunct> as one of http users.
- Fixed deployer detects wrong version. [#1842]
- Fixed crashes on including autoloader in recipe file. [#1602]


## v6.4.3
[v6.4.2...v6.4.3](https://github.com/deployphp/deployer/compare/v6.4.2...v6.4.3)

### Fixed
- Input option handling. [#1793]


## v6.4.2
[v6.4.1...v6.4.2](https://github.com/deployphp/deployer/compare/v6.4.1...v6.4.2)

### Fixed
- Improved ParallelExecutor::generateOptions to manage all types of InputOption. [#1792]


## v6.4.1
[v6.4.0...v6.4.1](https://github.com/deployphp/deployer/compare/v6.4.0...v6.4.1)

### Fixed
- Fixed http_user detection. [#1790]


## v6.4.0
[v6.3.0...v6.4.0](https://github.com/deployphp/deployer/compare/v6.3.0...v6.4.0)

### Added
- Support to define remote shell path via host-config. [#1708] [#1709] [#1709]
- Added `horizon:terminate` to the Laravel recipe.
- Added `migrations_config` option to the Symfony recipes to specify Doctrine migration configuration to use.
- Added recipe for sulu 2.0. [#1758]
- Added recipe for sulu 1.x and improve sulu 2.0 recipe. [#1764]
- Added `become` option for rsync upload.

### Changed
- Laravel recipe should not run `artisan:cache:clear` in `deploy` task.
- Pass-through the quiet mode into the git commands for updating code.
- `deploy:writable` will no longer be able to automatically detect http_user if there are multiple candidates for the role. [#1778]

### Fixed
- Fixed Range expansion when hosts.yml is loaded. [#1671]
- Fixed usage (only if present) of deploy_path config setting. [#1677]
- Fixed adding custom headers causes Httpie default header override.
- Fixed Laravel `laravel_version` failure.
- Fixed parser errors by adding the trim function to the changelog parser tokens.
- Fixed arguments for rsync to be properly escaped.
- Prevent multiple execution of task()->once() with --parallel and --limit option. [#1419]


## v6.3.0
[v6.2.0...v6.3.0](https://github.com/deployphp/deployer/compare/v6.2.0...v6.3.0)

### Added
- Added cache clear/warmup task for symfony4 recipe. [#1575]
- Added ability to use config params in host variables. [#1508]
- Make used shell configurable via `shellCommand`. [#1536]
- Added `cleanup_tty` option for `deploy:cleanup`.
- Added Prestashop 1.6 recipe.
- Set dedicated user variable under CI environments, if not provided by git-config.

### Changed
- Optimize locateBinaryPath() to create less subprocesses. [#1634]
- Laravel recipe runs migrations only once.

### Fixed
- Fixed that long http user name is not detected correctly. [#1580]
- Fixed missing `var/sessions` in Symfony 4 shared_dirs.
- Fixed warning with host without configuration. [#1583]
- Removed the `magento:enable` task from the Magento 2 recipe since the module states are defined in `app/etc/config.php` and this task overwrote that.
- Allow to set template file path in Drupal 7 recipe. [#1603]
- Fixed once() tasks that where being run multiple times with ParallelExecutor.
- Fixed high CPU usage when running in parallel.
- Fixed `deploy:writable` no need to specify http_user when using chgrp writable_mode.
- Fixed `deploy:shared` missing from some recipes. [#1663]
- Fixed missing `deploy:writable` entries in recipes. [#1661]


## v6.2.0
[v6.1.0...v6.2.0](https://github.com/deployphp/deployer/compare/v6.1.0...v6.2.0)

### Added
- Added cache clear/warmup task for symfony4 recipe. [#1575]
- Added ability to use config params in host variables. [#1508]
- Make used shell configurable via `shellCommand`. [#1536]

### Fixed
- Fixed that long http user name is not detected correctly. [#1580]
- Fixed missing `var/sessions` in Symfony 4 shared_dirs.
- Fixed warning with host without configuration. [#1583]


## v6.1.0
[v6.0.5...v6.1.0](https://github.com/deployphp/deployer/compare/v6.0.5...v6.1.0)

### Added
- Added debug:task command to display the order of task execution. [#1488]
- Added a description to the autocomplete command. [#1472]
- Added logging of unhandled exceptions into logfile. [#1481]
- Added default -H flag when using become. [#1556]
- Added Symfony 4 recipe. [#1437]

### Changed
- Throw meaningfull exception on errors in cd(). [#1480]
- Make sure Context::pop() is called when Callback errors in on(...) function. [#1513]
- Update silverstripe recipe to support silverstripe 4.
- Show standard output in exceptions when error output is empty. [#1554]
- Improve readability of command for finding web server user. [#1557]
- Update symfony package dependencies to ~4.0. [#1559]

### Fixed
- Fixed within() to also restore the working-path when the given callback throws a Exception. [#1463]
- Fixed `pcntl_fork` check for blacklisted Ubuntu LTS boxes. [#1476]
- Fixed shared dir/file paths containing variables (`{{variable}}`).


## v6.0.5
[v6.0.4...v6.0.5](https://github.com/deployphp/deployer/compare/v6.0.4...v6.0.5)

### Fixed
- Fixed `previous_release` param when `release_name` was overridden. [#1455]


## v6.0.4
[v6.0.3...v6.0.4](https://github.com/deployphp/deployer/compare/v6.0.3...v6.0.4)

### Changed
- Added support for GroupTask in invoke(). [#1364]
- Magento2 recipe optimizes the autoloader after the DI compilation. [#1365]
- Host's `roles()` API now can accept arrays too.
- Fixed bug where wrong time format is passed to touch when deploying assets. [#1390]
- Added artisan:migrate:fresh task for laravel recipe.
- Added platform config to composer.json. [#1426]
- Moved symfony finder to dev-dependency. [#1452]

### Fixed
- Fixed bug when config:hosts shows more than one table of hosts. [#1403]
- Fixed bug that inventory method does not return Proxy. [#1413]


## v6.0.3
[v6.0.2...v6.0.3](https://github.com/deployphp/deployer/compare/v6.0.2...v6.0.3)

### Changed
- Laravel version check defaults to 5.5 if not found. [#1352]

### Fixed
- Updated Laravel recipe to not run `artisan:optimize` on Laravel >= 5.5, as that command is now deprecated ([see upgrade notes](https://laravel.com/docs/5.5/upgrade)). [#1352]


## v6.0.2
[v6.0.1...v6.0.2](https://github.com/deployphp/deployer/compare/v6.0.1...v6.0.2)

### Fixed
- Fixed bug with curl ssh check in _Httpie_ util.


## v6.0.1
[v6.0.0...v6.0.1](https://github.com/deployphp/deployer/compare/v6.0.0...v6.0.1)

### Fixed
- Fixed stat url.


## v6.0.0
[v5.1.3...v6.0.0](https://github.com/deployphp/deployer/compare/v5.1.3...v6.0.0)

### Added
- Added possibility to use callable when setting 'default_stage'.
- Added console init template for TYPO3 CMS. [#1300]
- Added possibility to run a task only once. [#1311]
- Added `git_recursive` option.
- Added `shallow` task option.
- Added `deploy:info` task.
- Added `writable_tty` option for `deploy:writable`.
- Added `default_timeout` option. [#1256]
- Added `user` parameter.

### Changed
- Changed `branch` parameter and option behavior.
- Extended `task` func to support callables.
- Renamed `env_vars` to `env`.

### Fixed
- Improved the way `ParallelExecutor` handles option parameters.
- Fixed no `stage` argument in parallel mode. [#1299]
- Improved environment variables management.
- Fixed `runLocally` to not cd into remote dir.

### Removed
- Removed `terminate_message` option.
- Removed `Result` class.


## v5.1.3
[v5.1.2...v5.1.3](https://github.com/deployphp/deployer/compare/v5.1.2...v5.1.3)

### Fixed
- Fixed bug with wrong version printed after self-update command.
- Fixed bug with excess option "--no-debug" in deploy:cache:clear task. [#1290]


## v5.1.2
[v5.1.1...v5.1.2](https://github.com/deployphp/deployer/compare/v5.1.1...v5.1.2)

### Changed
- Improved `config:current` output (print each host's current release).
- Fixed cache clearing in the Symfony recipe (now runs both cache:clear and cache:warmup). [#1283]

### Fixed
- Fixed bug where `ParallelExecutor` threw an error when custom options were added.
- Fixed bug with parallel deploy in multi user envirouments. [#1269]


## v5.1.1
[v5.1.0...v5.1.1](https://github.com/deployphp/deployer/compare/v5.1.0...v5.1.1)

### Fixed
- Fixed bug with `self-update` warnings. [#1226]


## v5.1.0
[v5.0.3...v5.1.0](https://github.com/deployphp/deployer/compare/v5.0.3...v5.1.0)

### Added
- Check what `unzip` exists in `deploy:vendors` task.
- Added `dep run` command. [#1263]
- Added new `-o` option which allow to override default configuration.
- Added `dep autocomplete` command.
- Added `dep config:hosts` task to show inventory.

### Changed
- Use either one of `command`, `which` or `type` commands to locate custom binary path.

### Fixed
- Fixed parallel execution with non-standart php bin path. [#1265]
- Fixed ssh multiplexing initialization. [#1268]
- Fixed exit code on error. [#1236]
- Fixed bug with deploying in parallel to same host. [#1271]


## v5.0.3
[v5.0.2...v5.0.3](https://github.com/deployphp/deployer/compare/v5.0.2...v5.0.3)

### Fixed
- Fixed a parsing of laravel version in output. [#1252]


## v5.0.2
[v5.0.1...v5.0.2](https://github.com/deployphp/deployer/compare/v5.0.1...v5.0.2)

### Added
- Added `laravel_version` param. [#1246]

### Fixed
- Fixed upload / download with optional rsync ssh options. [#1227]
- Disable maintenance mode when Magento2 deployment fails. [#1251]
- Fixed storage link error when deploying Laravel < 5.3. [#1246]


## v5.0.1
[v5.0.0...v5.0.1](https://github.com/deployphp/deployer/compare/v5.0.0...v5.0.1)

### Added
- Exception when no task will be executed.
- Check for php7 in phar.

### Fixed
- Throw the correct exception on git --reference fail.
- Check if multiplexing is working before continuing. [#1192]
- Fixed upload with non-standard SSH port. [#1218]
- Ensure that host roles are treated as an array.


## v5.0.0
[v5.0.0-beta.3...v5.0.0](https://github.com/deployphp/deployer/compare/v5.0.0-beta.3...v5.0.0)

### Changed
- Working path default is `release_path` instead of home for simple tasks. [#1205]

### Fixed
- Fixed ssh multiplexing master connection initializing.
- Fixed `dep ssh` command. [#1204]
- Fixed `dep config:current` task.


## v5.0.0-beta.3
[v5.0.0-beta.2...v5.0.0-beta.3](https://github.com/deployphp/deployer/compare/v5.0.0-beta.2...v5.0.0-beta.3)

### Added
- Added `Request` class for get/post json requests.
- Added host's `addSshFlag` and `addSshOption` methods.

### Changed
- Allow to configure multiplexing. [#1165]

### Fixed
- Fixed command parsing in runLocally func.
- Fixed releases list and cleanup task. [#1175]


## v5.0.0-beta.2
[v5.0.0-beta.1...v5.0.0-beta.2](https://github.com/deployphp/deployer/compare/v5.0.0-beta.1...v5.0.0-beta.2)

### Added
- Added console init template for Yii2 basic and advanced receipe. [#1146]
- Added `artisan:storage:link` task to the Laravel recipe to symlink the public storage directory. [#1152]
- Added `previous_release` var.

### Changed
- Error message on locked release. [#1145]

### Fixed
- Fixed task order init/shared for yii2-app-advanced.php. [#1143]


## v5.0.0-beta.1
[v4.3.0...v5.0.0-beta.1](https://github.com/deployphp/deployer/compare/v4.3.0...v5.0.0-beta.1)

### Added
- Added `use_atomic_symlink` and `use_relative_symlink` option [14a8f8](https://github.com/deployphp/deployer/pull/1092/commits/14a8f8f9c4ebbc7da45c2b6b7c3c00a51b563ccf).
- Added `Ssh\Client`. [#1092]
- Added host ranges. [#1092]
- Added --hosts and --roles options. [#1092]
- Added `on` function. [#1092]
- Added `host` and `localhost`. [#1092]
- Added persistent config. [#1092]
- Added `--log` option. [#1092]
- Added `cleanup_use_sudo`. [#330]

### Changed
- `server` refactored to `host`. [#1092]
- `Enviroment` refactored to `Configuration`. [#1092]
- Phpunit test refactored. [#1092]
- `upload` and `download` now uses rsync. [#1092]
- Only native ssh client for now. [#1092]
- Task `current` to `config:current`. [#1092]
- `onFailure` to `fail`. [#1092]


## v4.3.0
[v4.2.1...v4.3.0](https://github.com/deployphp/deployer/compare/v4.2.1...v4.3.0)

### Added
- Added support for multiple choice questions. [#1076]
- Added a way to retrieve a defined task. [#1008]
- Added support for configFile in the NativeSsh implementation. [#979]
- Added `--no-hooks` option for running commands without `before()` and `after()`. [#1061]
- Added a usefull error when ask*() is not used wihtin a task(). [#1083]

### Changed
- Parse hyphens in environment setting names. [#1073]
- Autoload functions via Composer. [#1015]
- Added task queue:restart for Laravel recipe. [#1007]
- Changed output of errors for native ssh. [#1012]

### Fixed
- Fixed `Can not share same dirs` for shared folders having similar names. [#995]
- Fixed scalar override on recursive option merge. [#1003]
- Fixed incompatible PHP 7.0 syntax. [#1020]
- Fixed an issue with the output of ls in releases_list. [#1004] [#1036]
- Fixed possibility to use PEM files with Native SSH.
- Fixed old releases not being cleaned up when keep_releases reduced by more than half.
- Fixed creating non-existed `writable_dirs`. [#1000]
- Fixed uploading files with spaces in a path via Native SSH. [#1010]
- Fixed merge of string array config options. [#1067]
- Fixed uploading of files containing spaces. [#1077]
- Fixed download of files when filename remote contains spaces. [#1082]


## v4.2.1
[v4.2.0...v4.2.1](https://github.com/deployphp/deployer/compare/v4.2.0...v4.2.1)

### Fixed
- Fixed `deployer/phar-update` dependency for composer installation.


## v4.2.0
[v4.1.0...v4.2.0](https://github.com/deployphp/deployer/compare/v4.1.0...v4.2.0)

### Added
- Added pretty print to config:dump command.

### Changed
- `add()` now merges configuration options recursively. [#962]
- Added `writable_chmod_recursive` boolean option to enable non-recursive `chmod`.
- `ask()` now supports autocomplete. [#978]
- `release_path` returns `current_path` in non-deploy context. [#922]

### Fixed
- Fixed Flow recipe. [#986]
- Fixed `deploy:copy_dirs` task. [#914]
- Fixed default behavior for `working_path`. [#381]

### Removed
- Removed const `Environment::DEPLOY_PATH`.


## v4.1.0
[v4.0.2...v4.1.0](https://github.com/deployphp/deployer/compare/v4.0.2...v4.1.0)

### Added
- Added `testLocally` function (analog `test` fn).
- Added `ConfigurationException`.
- Show message on file download.
- Added support for multiplexing for NativeSsh. [#918]
- Added GracefulShutdownException.
- Added Magento2 recipe. [#911]

### Changed
- Server config `setPty` renamed to `pty`. [#953]
- Raised timeout for runLocally to 300 seconds. [#955]
- `deploy:unlock` now always successful. [#950]
- Added option `-L` to `setfacl`. [#956]
- Now throw exception on duplicates in `shared_dirs`.

### Fixed
- Fixed native ssh scp option.
- Fixed bug with `$httpGroup` guard clause. [#948]


## v4.0.2
[v4.0.1...v4.0.2](https://github.com/deployphp/deployer/compare/v4.0.1...v4.0.2)

### Fixed
- Fixed bug with copy shared files.
- Fixed recursive upload in native ssh.
- Improved Laravel recipe.
- Improved exceptions in runLocally.


## v4.0.1
[v4.0.0...v4.0.1](https://github.com/deployphp/deployer/compare/v4.0.0...v4.0.1)

### Added
- Added more writable modes.

### Changed
- Allowed init command overriding.
- Returned ACL as default writable mode.

### Fixed
- Fixed SilverStripe recipe.
- Fixed release sorting.
- Fixed release cleanup.
- Improved Symfony recipe.
- Fixed `DotArray` syntax in `Collection`.


[#2667]: https://github.com/deployphp/deployer/pull/2667
[#2656]: https://github.com/deployphp/deployer/issues/2656
[#2549]: https://github.com/deployphp/deployer/issues/2549
[#2545]: https://github.com/deployphp/deployer/issues/2545
[#2525]: https://github.com/deployphp/deployer/issues/2525
[#2509]: https://github.com/deployphp/deployer/issues/2509
[#2506]: https://github.com/deployphp/deployer/issues/2506
[#2503]: https://github.com/deployphp/deployer/issues/2503
[#2501]: https://github.com/deployphp/deployer/pull/2501
[#2488]: https://github.com/deployphp/deployer/pull/2488
[#2487]: https://github.com/deployphp/deployer/pull/2487
[#2486]: https://github.com/deployphp/deployer/pull/2486
[#2425]: https://github.com/deployphp/deployer/pull/2425
[#2423]: https://github.com/deployphp/deployer/issues/2423
[#2393]: https://github.com/deployphp/deployer/pull/2393
[#2392]: https://github.com/deployphp/deployer/issues/2392
[#2197]: https://github.com/deployphp/deployer/issues/2197
[#1994]: https://github.com/deployphp/deployer/issues/1994
[#1990]: https://github.com/deployphp/deployer/issues/1990
[#1989]: https://github.com/deployphp/deployer/issues/1989
[#1971]: https://github.com/deployphp/deployer/pull/1971
[#1969]: https://github.com/deployphp/deployer/issues/1969
[#1909]: https://github.com/deployphp/deployer/issues/1909
[#1899]: https://github.com/deployphp/deployer/pull/1899
[#1893]: https://github.com/deployphp/deployer/pull/1893
[#1881]: https://github.com/deployphp/deployer/pull/1881
[#1876]: https://github.com/deployphp/deployer/pull/1876
[#1842]: https://github.com/deployphp/deployer/pull/1842
[#1822]: https://github.com/deployphp/deployer/issues/1822
[#1820]: https://github.com/deployphp/deployer/pull/1820
[#1796]: https://github.com/deployphp/deployer/pull/1796
[#1793]: https://github.com/deployphp/deployer/pull/1793
[#1792]: https://github.com/deployphp/deployer/pull/1792
[#1790]: https://github.com/deployphp/deployer/pull/1790
[#1778]: https://github.com/deployphp/deployer/issues/1778
[#1775]: https://github.com/deployphp/deployer/pull/1775
[#1764]: https://github.com/deployphp/deployer/pull/1764
[#1758]: https://github.com/deployphp/deployer/pull/1758
[#1755]: https://github.com/deployphp/deployer/issues/1755
[#1709]: https://github.com/deployphp/deployer/issues/1709
[#1708]: https://github.com/deployphp/deployer/pull/1708
[#1677]: https://github.com/deployphp/deployer/pull/1677
[#1671]: https://github.com/deployphp/deployer/issues/1671
[#1663]: https://github.com/deployphp/deployer/issues/1663
[#1661]: https://github.com/deployphp/deployer/pull/1661
[#1634]: https://github.com/deployphp/deployer/pull/1634
[#1603]: https://github.com/deployphp/deployer/issues/1603
[#1602]: https://github.com/deployphp/deployer/issues/1602
[#1583]: https://github.com/deployphp/deployer/issues/1583
[#1580]: https://github.com/deployphp/deployer/pull/1580
[#1575]: https://github.com/deployphp/deployer/pull/1575
[#1559]: https://github.com/deployphp/deployer/pull/1559
[#1557]: https://github.com/deployphp/deployer/pull/1557
[#1556]: https://github.com/deployphp/deployer/pull/1556
[#1554]: https://github.com/deployphp/deployer/pull/1554
[#1536]: https://github.com/deployphp/deployer/pull/1536
[#1513]: https://github.com/deployphp/deployer/pull/1513
[#1508]: https://github.com/deployphp/deployer/issues/1508
[#1488]: https://github.com/deployphp/deployer/issues/1488
[#1481]: https://github.com/deployphp/deployer/issues/1481
[#1480]: https://github.com/deployphp/deployer/issues/1480
[#1479]: https://github.com/deployphp/deployer/issues/1479
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

<!-- DO NOT EDIT THIS FILE! -->
<!-- Instead edit recipe/symfony.php -->
<!-- Then run bin/docgen -->

# symfony

[Source](/recipe/symfony.php)


Symfony Configuration


* Require
  * [`recipe/common.php`](/docs/recipe/common.md)
* Config
  * [`symfony_env`](#symfony_env)
  * [`shared_dirs`](#shared_dirs)
  * [`shared_files`](#shared_files)
  * [`writable_dirs`](#writable_dirs)
  * [`clear_paths`](#clear_paths)
  * [`assets`](#assets)
  * [`dump_assets`](#dump_assets)
  * [`env`](#env)
  * [`composer_options`](#composer_options)
  * [`bin_dir`](#bin_dir)
  * [`var_dir`](#var_dir)
  * [`console_options`](#console_options)
  * [`migrations_config`](#migrations_config)
* Tasks
  * [`deploy:create_cache_dir`](#deploy:create_cache_dir) — 
  * [`deploy:assets`](#deploy:assets) — 
  * [`deploy:assets:install`](#deploy:assets:install) — 
  * [`deploy:assetic:dump`](#deploy:assetic:dump) — 
  * [`deploy:cache:clear`](#deploy:cache:clear) — 
  * [`deploy:cache:warmup`](#deploy:cache:warmup) — 
  * [`database:migrate`](#database:migrate) — 
  * [`deploy`](#deploy) — 

## Config
### symfony_env
[Source](/recipe/symfony.php#L12)

Symfony build set

### shared_dirs
[Source](/recipe/symfony.php#L15)

Symfony shared dirs

### shared_files
[Source](/recipe/symfony.php#L18)

Symfony shared files

### writable_dirs
[Source](/recipe/symfony.php#L21)

Symfony writable dirs

### clear_paths
[Source](/recipe/symfony.php#L24)

Clear paths

### assets
[Source](/recipe/symfony.php#L27)

Assets

### dump_assets
[Source](/recipe/symfony.php#L30)

Requires non symfony-core package `kriswallsmith/assetic` to be installed

### env
[Source](/recipe/symfony.php#L33)

Environment vars

### composer_options
[Source](/recipe/symfony.php#L39)



### bin_dir
[Source](/recipe/symfony.php#L46)

Adding support for the Symfony3 directory structure

### var_dir
[Source](/recipe/symfony.php#L47)



### console_options
[Source](/recipe/symfony.php#L55)

Symfony console opts

### migrations_config
[Source](/recipe/symfony.php#L61)

Migrations configuration file


## Tasks
### deploy:create_cache_dir
[Source](/recipe/symfony.php#L67)

Create cache dir

### deploy:assets
[Source](/recipe/symfony.php#L85)

Normalize asset timestamps

### deploy:assets:install
[Source](/recipe/symfony.php#L97)

Install assets from public dir of bundles

### deploy:assetic:dump
[Source](/recipe/symfony.php#L105)

Dump all assets to the filesystem

### deploy:cache:clear
[Source](/recipe/symfony.php#L114)

Clear Cache

### deploy:cache:warmup
[Source](/recipe/symfony.php#L121)

Warm up cache

### database:migrate
[Source](/recipe/symfony.php#L129)

Migrate database

### deploy
[Source](/recipe/symfony.php#L142)

Main task

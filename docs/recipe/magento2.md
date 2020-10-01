<!-- DO NOT EDIT THIS FILE! -->
<!-- Instead edit recipe/magento2.php -->
<!-- Then run bin/docgen -->

# magento2

[Source](/recipe/magento2.php)

Configuration


* Require
  * [`recipe/common.php`](/docs/recipe/common.md)
* Config
  * [`static_content_locales`](#static_content_locales)
  * [`shared_files`](#shared_files)
  * [`shared_dirs`](#shared_dirs)
  * [`writable_dirs`](#writable_dirs)
  * [`clear_paths`](#clear_paths)
* Tasks
  * [`magento:compile`](#magento:compile) — Compile magento di
  * [`magento:deploy:assets`](#magento:deploy:assets) — Deploy assets
  * [`magento:maintenance:enable`](#magento:maintenance:enable) — Enable maintenance mode
  * [`magento:maintenance:disable`](#magento:maintenance:disable) — Disable maintenance mode
  * [`magento:upgrade:db`](#magento:upgrade:db) — Upgrade magento database
  * [`magento:cache:flush`](#magento:cache:flush) — Flush Magento Cache
  * [`deploy:magento`](#deploy:magento) — Magento2 deployment operations
  * [`deploy`](#deploy) — Deploy your project

## Config
### static_content_locales
[Source](/recipe/magento2.php#L11)

By default setup:static-content:deploy uses `en_US`. 
To change that, simply put set('static_content_locales', 'en_US de_DE');` 
in you deployer script.

### shared_files
[Source](/recipe/magento2.php#L13)



### shared_dirs
[Source](/recipe/magento2.php#L17)



### writable_dirs
[Source](/recipe/magento2.php#L31)



### clear_paths
[Source](/recipe/magento2.php#L37)




## Tasks
### magento:compile
[Source](/recipe/magento2.php#L48)

Tasks

### magento:deploy:assets
[Source](/recipe/magento2.php#L54)



### magento:maintenance:enable
[Source](/recipe/magento2.php#L59)



### magento:maintenance:disable
[Source](/recipe/magento2.php#L64)



### magento:upgrade:db
[Source](/recipe/magento2.php#L69)



### magento:cache:flush
[Source](/recipe/magento2.php#L74)



### deploy:magento
[Source](/recipe/magento2.php#L79)



### deploy
[Source](/recipe/magento2.php#L89)



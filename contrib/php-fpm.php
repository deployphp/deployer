<?php
/*

:::caution
Do **not** reload php-fpm. Some user requests could fail or not complete in the
process of reloading.

Instead, configure your server [properly](https://ï.at/avoid-php-fpm-reloading). If you're using Deployer's provision
recipe, it's already configured the right way and no php-fpm reload is needed.
:::

## Configuration

- `php_fpm_version` – The PHP-fpm version. For example: `8.0`.
- `php_fpm_service` – The full name of the PHP-fpm service. Defaults to `php{{php_fpm_version}}-fpm`.
- `php_fpm_command` – The command to run to reload PHP-fpm. Defaults to `sudo systemctl reload {{php_fpm_service}}`.

## Usage

Start by explicitely providing the current version of PHP-version using the `php_fpm_version`.
Alternatively, you may use any of the options above to configure how PHP-fpm should reload.

Then, add the `php-fpm:reload` task at the end of your deployments by using the `after` method like so.

```php
set('php_fpm_version', '8.0');
after('deploy', 'php-fpm:reload');
```

 */
namespace Deployer;

// Automatically detects by using {{bin/php}}.
set('php_fpm_version', function () {
    return run('{{bin/php}} -r "printf(\'%d.%d\', PHP_MAJOR_VERSION, PHP_MINOR_VERSION);"');
});

set('php_fpm_service', 'php{{php_fpm_version}}-fpm');

desc('Reloads the php-fpm service');
task('php-fpm:reload', function () {
    warning('Avoid reloading php-fpm [ï.at/avoid-php-fpm-reloading]');
    run('sudo systemctl reload {{php_fpm_service}}');
});

<?php
/*
## Installing

Add to your _deploy.php_

```php
require 'contrib/php-fpm.php';
```

## Configuration

- `php_fpm_version` – The PHP-fpm version. For example: `8.0`.
- `php_fpm_service` – The full name of the PHP-fpm service. Defaults to `php{{php_fpm_version}}-fpm`.
- `php_fpm_command` – The command to run to reload PHP-fpm. Defaults to `echo "" | sudo -S /usr/sbin/service {{php_fpm_service}} reload`.

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

set('php_fpm_version', function () {
    $phpFpmProcess = run("ps aux | grep php-fpm | grep 'master process'");

    if (! preg_match('/^.*master process.*(\d\.\d).*$/', $phpFpmProcess, $match)) {
        throw new \Exception('Please provide the PHP-fpm version using the `php_fpm_version` option.');
    }

    return $match[1];
});
set('php_fpm_service', 'php{{php_fpm_version}}-fpm');
set('php_fpm_command', 'echo "" | sudo -S /usr/sbin/service {{php_fpm_service}} reload');

desc('Reload the php-fpm service');
task('php-fpm:reload', function () {
    run('{{php_fpm_command}}');
});

<?php
/*
Add to your _deploy.php_

```php
require 'contrib/cachetool.php';
```

## Configuration

- **cachetool** *(optional)*: accepts a *string* with the unix socket or ip address to php5-fpm. If `cachetool` is not given, then the application will look for a `cachetool.yml` file and read the configuration from there.

    ```php
    set('cachetool', '/var/run/php5-fpm.sock');
    // or
    set('cachetool', '127.0.0.1:9000');
    ```

You can also specify different cachetool settings for each host:
```php
host('staging')
    ->set('cachetool', '127.0.0.1:9000');

host('production')
    ->set('cachetool', '/var/run/php5-fpm.sock');
```

By default, if no `cachetool` parameter is provided, this recipe will fallback to the global setting.

## Usage

Since APC/APCu and OPcache deal with compiling and caching files, they should be executed right after the symlink is created for the new release:

```php
after('deploy:symlink', 'cachetool:clear:opcache');
// or
after('deploy:symlink', 'cachetool:clear:apc');
// or
after('deploy:symlink', 'cachetool:clear:apcu');
```

## Read more

Read more information about cachetool on the website:
http://gordalina.github.io/cachetool/
 */

namespace Deployer;

set('cachetool', '');
set('cachetool_args', '');
set('cachetool_binary', function () {
    return run("{{bin/php}} -r \"echo (PHP_VERSION_ID <= 50640) ? 'cachetool-3.2.1.phar' : ((PHP_VERSION_ID <= 70133) ? 'cachetool-4.1.1.phar' : 'cachetool.phar');\"");
});
set('bin/cachetool', function () {
    $cachetool_binary = get('cachetool_binary');
    $cachetool_binary = locateBinaryPath($cachetool_binary);

    if (empty($cachetool_binary)) {
        run("cd {{release_path}} && curl -sSO https://gordalina.github.io/cachetool/downloads/{{cachetool_binary}}");
        $cachetool_binary = '{{release_path}}/{{cachetool_binary}}';
    }

    return $cachetool_binary;
});
set('cachetool_options', function () {
    $options = get('cachetool');
    $fullOptions = get('cachetool_args');

    if (strlen($fullOptions) > 0) {
        $options = "{$fullOptions}";
    } elseif (strlen($options) > 0) {
        $options = "--fcgi={$options}";
    }

    return $options;
});

desc('Clearing APC system cache');
task('cachetool:clear:apc', function () {
    run("cd {{release_path}} && {{bin/php}} {{bin/cachetool}} apc:cache:clear system {{cachetool_options}}");
});

/**
 * Clear opcache cache
 */
desc('Clearing OPcode cache');
task('cachetool:clear:opcache', function () {
    run("cd {{release_path}} && {{bin/php}} {{bin/cachetool}} opcache:reset {{cachetool_options}}");
});

/**
 * Clear APCU cache
 */
desc('Clearing APCu system cache');
task('cachetool:clear:apcu', function () {
    run("cd {{release_path}} && {{bin/php}} {{bin/cachetool}} apcu:cache:clear {{cachetool_options}}");
});

/**
 * Clear file status cache, including the realpath cache
 */
desc('Clearing file status and realpath caches');
task('cachetool:clear:stat', function () {
    run("cd {{release_path}} && {{bin/php}} {{bin/cachetool}} stat:clear {{cachetool_options}}");
});

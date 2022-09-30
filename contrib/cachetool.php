<?php
/*

## Configuration

- **cachetool:socket:tcp** *(optional)*: accepts a *string* with IP address and port to the php-fpm TCP socket

    ```php
    set('cachetool:socket:tcp', '127.0.0.1:9000')
    ```

- **cachetool:socket:glob** *(optional)*: accepts a path or glob pattern to the php-fpm unix socket(s)

    ```php
    set('cachetool:socket:glob', '/var/run/php-*.sock')
    ```

If neither `cachetool:socket:tcp` nor `cachetool:socket:glob` is given, then the tool will look for a `cachetool.yml` file in the current or any parent directory and read the configuration from there. See [cachetool documentation](https://github.com/gordalina/cachetool#configuration-file)

You can also specify different cachetool settings for each host:
```php
host('staging')
    ->set('cachetool:socket:tcp', '127.0.0.1:9000');

host('production')
    ->set('cachetool:socket:glob', '/var/run/php-fpm.sock');
```

By default, if no `cachetool` parameter is provided, this recipe will fallback to the global setting.

If your deployment user does not have permission to access the php-fpm.sock, you can alternatively use
the web adapter that creates a temporary php file and makes a web request to it with a configuration like
```php
set('cachetool_args', '--web --web-path=./public --web-url=https://{{hostname}}');
```

## Usage

Since APCu and OPcache deal with compiling and caching files, they should be executed right after the symlink is created for the new release:

```php
after('deploy:symlink', 'cachetool:clear:opcache');
// or
after('deploy:symlink', 'cachetool:clear:apcu');
```

## Read more

Read more information about cachetool on the website:
http://gordalina.github.io/cachetool/
 */
namespace Deployer;

set('cachetool', '');
set('cachetool_url', 'https://github.com/gordalina/cachetool/releases/download/7.0.0/cachetool.phar');
set('cachetool_args', '');
set('bin/cachetool', function () {
    if (!test('[ -f {{release_or_current_path}}/cachetool.phar ]')) {
        run("cd {{release_or_current_path}} && curl -sLO {{cachetool_url}}");
    }
    return '{{release_or_current_path}}/cachetool.phar';
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

/**
 * Clear opcache cache
 */
desc('Clears OPcode cache');
task('cachetool:clear:opcache', function () {
    run("cd {{release_or_current_path}} && {{bin/php}} {{bin/cachetool}} opcache:reset {{cachetool_options}}");
});

/**
 * Clear APCU cache
 */
desc('Clears APCu system cache');
task('cachetool:clear:apcu', function () {
    run("cd {{release_or_current_path}} && {{bin/php}} {{bin/cachetool}} apcu:cache:clear {{cachetool_options}}");
});

/**
 * Clear file status cache, including the realpath cache
 */
desc('Clears file status and realpath caches');
task('cachetool:clear:stat', function () {
    run("cd {{release_or_current_path}} && {{bin/php}} {{bin/cachetool}} stat:clear {{cachetool_options}}");
});

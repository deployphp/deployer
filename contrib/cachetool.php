<?php
/*

## Configuration

- **cachetool** *(optional)*: accepts a *string* or an *array* of strings with the unix socket or ip address to php-fpm. If `cachetool` is not given, then the application will look for a configuration file. The file must be named .cachetool.yml or .cachetool.yaml. CacheTool will look for this file on the current directory and in any parent directory until it finds one. If the paths above fail it will try to load /etc/cachetool.yml or /etc/cachetool.yaml configuration file.

    ```php
    set('cachetool', '/var/run/php-fpm.sock');
    // or
    set('cachetool', '127.0.0.1:9000');
    // or
    set('cachetool', ['/var/run/php-fpm.sock', '/var/run/php-fpm-other.sock']);
    ```

You can also specify different cachetool settings for each host:
```php
host('staging')
    ->set('cachetool', '127.0.0.1:9000');

host('production')
    ->set('cachetool', '/var/run/php-fpm.sock');
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
/**
 * URL to download cachetool from if it is not available
 *
 * CacheTool 9.x works with PHP >=8.1
 * CacheTool 8.x works with PHP >=8.0
 * CacheTool 7.x works with PHP >=7.3
 */
set('cachetool_url', 'https://github.com/gordalina/cachetool/releases/download/9.0.0/cachetool.phar');
set('cachetool_args', '');
set('bin/cachetool', function () {
    if (!test('[ -f {{release_or_current_path}}/cachetool.phar ]')) {
        run("cd {{release_or_current_path}} && curl -sLO {{cachetool_url}}");
    }
    return '{{release_or_current_path}}/cachetool.phar';
});
set('cachetool_options', function () {
    $options = (array)get('cachetool');
    $fullOptions = (string)get('cachetool_args');
    $return = [];

    if ($fullOptions !== '') {
        $return = [$fullOptions];
    } elseif (count($options) > 0) {
        foreach ($options as $option) {
            if (is_string($option) && $option !== '') {
                $return[] = "--fcgi={$option}";
            }
        }
    }

    return $return ?: [''];
});

/**
 * Clear opcache cache
 */
desc('Clears OPcode cache');
task('cachetool:clear:opcache', function () {
    $options = get('cachetool_options');
    foreach ($options as $option) {
        run("cd {{release_or_current_path}} && {{bin/php}} {{bin/cachetool}} opcache:reset $option");
    }
});

/**
 * Clear APCu cache
 */
desc('Clears APCu system cache');
task('cachetool:clear:apcu', function () {
    $options = get('cachetool_options');
    foreach ($options as $option) {
        run("cd {{release_or_current_path}} && {{bin/php}} {{bin/cachetool}} apcu:cache:clear $option");
    }
});

/**
 * Clear file status cache, including the realpath cache
 */
desc('Clears file status and realpath caches');
task('cachetool:clear:stat', function () {
    $options = get('cachetool_options');
    foreach ($options as $option) {
        run("cd {{release_or_current_path}} && {{bin/php}} {{bin/cachetool}} stat:clear $option");
    }
});

<?php
/* (c) Samuel Gordalina <samuel.gordalina@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

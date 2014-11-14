<?php
/* (c) Samuel Gordalina <samuel.gordalina@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Suggested usage
 *
 * set('cachetool', '/var/run/php5-fpm.sock');
 * or
 * set('cachetool', '127.0.0.1:9000');
 * or
 * have a cachetool.yml in your directory (see: https://github.com/gordalina/cachetool#configuration-file)
 *
 * after('deploy:symlink', 'cachetool:clear:opcache');
 * or
 * after('deploy:symlink', 'cachetool:clear:apc');
 *
 * Read more at: https://github.com/gordalina/cachetool
 */

/**
 * Clear apc cache
 */
task('cachetool:clear:apc', function () {
    $releasePath = env()->getReleasePath();
    $options = get('cachetool', '');

    if (strlen($options)) {
        $options = "--fcgi={$options}";
    }

    cd($releasePath);
    $hasCachetool = run("if [ -e $releasePath/cachetool.phar ]; then echo 'true'; fi");

    if ('true' !== $hasCachetool) {
        run("curl -sO http://gordalina.github.io/cachetool/downloads/cachetool.phar");
    }

    run("php cachetool.phar apc:cache:clear system {$options}");
});

/**
 * Clear opcache cache
 */
task('cachetool:clear:opcache', function () {
    $releasePath = env()->getReleasePath();
    $options = get('cachetool', '');

    if (strlen($options)) {
        $options = "--fcgi={$options}";
    }

    cd($releasePath);
    $hasCachetool = run("if [ -e $releasePath/cachetool.phar ]; then echo 'true'; fi");

    if ('true' !== $hasCachetool) {
        run("curl -sO http://gordalina.github.io/cachetool/downloads/cachetool.phar");
    }

    run("php cachetool.phar opcache:reset {$options}");
});

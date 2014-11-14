<?php
/* (c) Samuel Gordalina <samuel.gordalina@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Suggested usage
 *
 * before('cleanup', 'cachetool:clear:opcache');
 * or
 * before('cleanup', 'cachetool:clear:apc');
 */

/**
 * Clear apc cache
 */
task('cachetool:clear:apc', function () {
    $releasePath = env()->getReleasePath();
    $options = get('cachetool', '');

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

    cd($releasePath);
    $hasCachetool = run("if [ -e $releasePath/cachetool.phar ]; then echo 'true'; fi");

    if ('true' !== $hasCachetool) {
        run("curl -sO http://gordalina.github.io/cachetool/downloads/cachetool.phar");
    }

    run("php cachetool.phar opcache:reset {$options}");
});

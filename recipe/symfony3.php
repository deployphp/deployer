<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/symfony.php';

/**
 * Symfony 3 Configuration
 */
 
/**
 * Dump all assets to the filesystem
 *
 * This overrides the Symfony 2 assetic:dump command
 * in favor of the new assets:install command.
 */
task('deploy:assetic:dump', function () {
    if (!get('dump_assets')) {
        return;
    }

    run('{{bin/php}} {{release_path}}/' . trim(get('bin_dir'), '/') . '/console assets:install --env={{env}} --no-debug {{release_path}}/web');
})->desc('Dump assets');

// Symfony shared dirs
set('shared_dirs', ['var/logs', 'var/sessions']);

// Symfony writable dirs
set('writable_dirs', ['var/cache', 'var/logs', 'var/sessions']);

set('bin_dir', 'bin');
set('var_dir', 'var');

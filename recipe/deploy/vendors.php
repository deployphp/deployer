<?php

namespace Deployer;

set('composer_action', 'install');

set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

/**
 * Can be used to choose what composer version to install.
 * Valid values are any that are [listed here](https://getcomposer.org/download/).
 *
 * For example:
 * ```php
 *     set('composer_version', '10.10.15')
 * ```
 */
set('composer_version', null);

/**
 * Set this variable to stable, snapshot, preview, 1 or 2 to select which Composer channel to use
 */
set('composer_channel', null);

set('bin/composer', function () {
    if (commandExist('composer')) {
        return '{{bin/php}} ' . locateBinaryPath('composer');
    }

    if (test('[ -f {{deploy_path}}/.dep/composer.phar ]')) {
        return '{{bin/php}} {{deploy_path}}/.dep/composer.phar';
    }

    $composerVersionToInstall = get('composer_version', null);
    $composerChannel = get('composer_channel', null);
    $installCommand = "cd {{release_path}} && curl -sS https://getcomposer.org/installer | {{bin/php}}";

    if ($composerVersionToInstall) {
        $installCommand .= " -- --version=" . $composerVersionToInstall;
    } elseif ($composerChannel) {
        $composerValidChannels = ['stable', 'snapshot', 'preview', '1', '2',];
        if (!in_array($composerChannel, $composerValidChannels)) {
            throw new \Exception('Selected Composer channel ' . $composerChannel . ' is not valid. Valid channels are: ' . implode(', ', $composerValidChannels));
        }
        $installCommand .= " -- --" . $composerChannel;
    }

    run($installCommand);
    run('mv {{release_path}}/composer.phar {{deploy_path}}/.dep/composer.phar');
    return '{{bin/php}} {{deploy_path}}/.dep/composer.phar';
});

desc('Installing vendors');
task('deploy:vendors', function () {
    if (!commandExist('unzip')) {
        warning('To speed up composer installation setup "unzip" command with PHP zip extension.');
    }
    run('cd {{release_path}} && {{bin/composer}} {{composer_action}} {{composer_options}} 2>&1');
});

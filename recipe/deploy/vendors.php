<?php

namespace Deployer;

set('composer_action', 'install');

set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

/**
 * Can be used to choose what composer version to install.
 * Valid values are any that are listed here: https://getcomposer.org/download/
 *
 * For example:
 * set('composer_version', '10.10.15')
 */
set('composer_version', null);

set('bin/composer', function () {
    if (commandExist('composer')) {
        return '{{bin/php}} ' . locateBinaryPath('composer');
    }

    if (test('[ -f {{deploy_path}}/.dep/composer.phar ]')) {
        return '{{bin/php}} {{deploy_path}}/.dep/composer.phar';
    }

    $composerVersionToInstall = get('composer_version', null);
    $installCommand = "cd {{release_path}} && curl -sS https://getcomposer.org/installer | {{bin/php}}";

    if ($composerVersionToInstall) {
        $installCommand .= " -- --version=" . $composerVersionToInstall;
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

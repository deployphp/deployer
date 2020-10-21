<?php

namespace Deployer;

set('composer_action', 'install');

set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

set('bin/composer', function () {
    if (commandExist('composer')) {
        return '{{bin/php}} ' . locateBinaryPath('composer');
    }

    if (test('[ -f {{deploy_path}}/.dep/composer.phar ]')) {
        return '{{bin/php}} {{deploy_path}}/.dep/composer.phar';
    }

    run("cd {{release_path}} && curl -sS https://getcomposer.org/installer | {{bin/php}}");
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

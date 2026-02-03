<?php

namespace Deployer;

set('composer_action', 'install');
set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');
set('composer_version', null);

// Returns Composer binary path if found. Otherwise, tries to install composer to `.dep/composer.phar`.
set('bin/composer', function () {
    if (test('[ -f {{deploy_path}}/.dep/composer.phar ]')) {
        if (empty(get('composer_version')) || preg_match(parse('/Composer.*{{composer_version}}/'), run('{{bin/php}} {{deploy_path}}/.dep/composer.phar --version'))) {
            return '{{bin/php}} {{deploy_path}}/.dep/composer.phar';
        }
    }

    if (commandExist('composer')) {
        if (empty(get('composer_version')) || preg_match(parse('/Composer.*{{composer_version}}/'), run('{{bin/php}} ' . which('composer') . ' --version'))) {
            return '{{bin/php}} ' . which('composer');
        }
    }

    $versionAsName = get('composer_version') ? ' {{composer_version}}' : '';
    $versionAsOption = get('composer_version') ? ' -- --version={{composer_version}}' : '';
    warning("Composer{$versionAsName} wasn't found. Installing to \"{{deploy_path}}/.dep/composer.phar\".");
    run("cd {{deploy_path}} && curl -sS https://getcomposer.org/installer | {{bin/php}}{$versionAsOption}");
    run('mv {{deploy_path}}/composer.phar {{deploy_path}}/.dep/composer.phar');
    return '{{bin/php}} {{deploy_path}}/.dep/composer.phar';
});

desc('Installs vendors');
task('deploy:vendors', function () {
    if (!commandExist('unzip')) {
        warning('To speed up composer installation setup "unzip" command with PHP zip extension.');
    }
    run('cd {{release_or_current_path}} && {{bin/composer}} {{composer_action}} {{composer_options}} 2>&1');
});

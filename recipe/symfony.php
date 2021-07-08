<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['symfony']);

set('symfony_version', function () {
    $result = run('{{bin/console}} --version');
    preg_match_all('/(\d+\.?)+/', $result, $matches);
    return $matches[0][0] ?? 5.0;
});

set('shared_dirs', [
    'var/log',
    'var/sessions'
]);

set('shared_files', [
    '.env.local'
]);

set('writable_dirs', [
    'var'
]);

set('migrations_config', '');

set('bin/console', '{{bin/php}} {{release_or_current_path}}/bin/console');

set('console_options', function () {
    return '--no-interaction';
});

desc('Migrate database');
task('database:migrate', function () {
    $options = '--allow-no-migration';
    if (get('migrations_config') !== '') {
        $options = "$options --configuration={{release_or_current_path}}/{{migrations_config}}";
    }

    run("cd {{release_or_current_path}} && {{bin/console}} doctrine:migrations:migrate $options {{console_options}}");
});

desc('Clear cache');
task('deploy:cache:clear', function () {
    // composer install scripts usually clear and warmup symfony cache
    // so we only need to do it if composer install was run with --no-scripts
    if (false !== strpos(get('composer_options', ''), '--no-scripts')) {
        run('{{bin/console}} cache:clear {{console_options}}');
    }
});

desc('Deploy project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:cache:clear',
    'deploy:publish',
]);

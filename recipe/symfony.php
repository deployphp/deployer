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
    'var/sessions']
);

set('shared_files', [
    '.env.local.php',
    '.env.local'
]);

set('writable_dirs', [
    'var'
]);

set('migrations_config', '');

set('bin/console', '{{bin/php}} {{release_path}}/bin/console');

set('console_options', function () {
    return '--no-interaction';
});

desc('Migrate database');
task('database:migrate', function () {
    $options = '--allow-no-migration';
    if (get('migrations_config') !== '') {
        $options = "$options --configuration={{release_path}}/{{migrations_config}}";
    }

    run("cd {{release_path}} && {{bin/console}} doctrine:migrations:migrate $options {{console_options}}");
});

desc('Clear cache');
task('deploy:cache:clear', function () {
    run('{{bin/console}} cache:clear {{console_options}} --no-warmup');
});

desc('Warm up cache');
task('deploy:cache:warmup', function () {
    run('{{bin/console}} cache:warmup {{console_options}}');
});

desc('Deploy project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:cache:clear',
    'deploy:cache:warmup',
    'deploy:publish',
]);

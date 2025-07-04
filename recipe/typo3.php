<?php

namespace Deployer;

require_once __DIR__ . '/common.php';
require_once 'contrib/rsync.php';

add('recipes', ['typo3']);

set('composer_config', function () {
    return json_decode(file_get_contents('./composer.json'), true, 512, JSON_THROW_ON_ERROR);
});

/**
 * DocumentRoot / WebRoot for the TYPO3 installation
 */
set('typo3_webroot', function () {
    $composerConfig = get('composer_config');

    if ($composerConfig['extra']['typo3/cms']['web-dir'] ?? false) {
        return $composerConfig['extra']['typo3/cms']['web-dir'];
    }

    return 'public';
});

/**
 * Path to TYPO3 cli
 */
set('bin/typo3', function () {
    $composerConfig = get('composer_config');

    if ($composerConfig['config']['bin-dir'] ?? false) {
        return $composerConfig['config']['bin-dir'] . '/typo3';
    }

    return 'vendor/bin/typo3';
});

/**
 * Log files to display when running `./vendor/bin/dep logs:app`
 */
set('log_files', 'var/log/typo3_*.log');

/**
 * Shared directories
 */
set('shared_dirs', [
    '{{typo3_webroot}}/fileadmin',
    '{{typo3_webroot}}/typo3temp',
    'var/session',
    'var/log',
    'var/lock',
    'var/charset',
]);

/**
 * Shared files
 */
set('shared_files', [
    'config/system/settings.php',
    '.env',
]);

/**
 * Writeable directories
 */
set('writable_dirs', [
    '{{typo3_webroot}}/fileadmin',
    '{{typo3_webroot}}/typo3temp',
    'var',
]);

/**
 * Composer options
 */
set('composer_options', ' --no-dev --verbose --prefer-dist --no-progress --no-interaction --optimize-autoloader');


/**
 * If set in the config this recipe uses rsync. Default: false (use the Git repository)
 */
set('use_rsync', false);

set('update_code_task', function () {
    return get('use_rsync') ? 'rsync' : 'deploy:update_code';
});

task('typo3:update_code', function () {
    invoke(get('update_code_task'));
});

$exclude = [
    '.Build',
    '.git',
    '.gitlab',
    '.ddev',
    '.deployer',
    '.idea',
    '.DS_Store',
    '.gitlab-ci.yml',
    '.npm',
    'deploy.yaml',
    'package.json',
    'package-lock.json',
    'node_modules/',
    'var/',
    'public/fileadmin/',
    'public/typo3temp/',
];

set('rsync', [
    'exclude' => array_merge(get('shared_dirs'), get('shared_files'), $exclude),
    'exclude-file' => false,
    'include' => ['vendor'],
    'include-file' => false,
    'filter' => ['dir-merge,-n /.gitignore'],
    'filter-file' => false,
    'filter-perdir' => false,
    'flags' => 'avz',
    'options' => ['delete', 'keep-dirlinks', 'links'],
    'timeout' => 600,
]);

desc('TYPO3 - Cache warmup for system caches');
task('typo3:cache:warmup', function () {
    cd('{{release_path}}');
    run('{{bin/php}} {{bin/typo3}} cache:warmup --group system');
});

desc('TYPO3 - Cache clearing for frontend caches');
task('typo3:cache:flush', function () {
    cd('{{release_path}}');
    run('{{bin/php}} {{bin/typo3}} cache:flush --group pages');
});

desc('TYPO3 - Cache warmup for frontend caches');
task('typo3:cache:pages:warmup', function () {
    cd('{{release_path}}');
    run('{{bin/php}} {{bin/typo3}} cache:warmup --group pages');
});

desc('TYPO3 - Update the language files of all activated extensions');
task('typo3:language:update', function () {
    cd('{{release_path}}');
    run('{{bin/php}} {{bin/typo3}} language:update');
});

desc('TYPO3 - Set up all extensions');
task('typo3:extension:setup', function () {
    cd('{{release_path}}');
    run('{{bin/php}} {{bin/typo3}} extension:setup');
});

/**
 * Configure "deploy" task group.
 */
desc('Deploys a TYPO3 project');
task('deploy', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'typo3:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'typo3:cache:warmup',
    'typo3:extension:setup',
    'typo3:language:update',
    'typo3:cache:flush',
    'typo3:cache:pages:warmup',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:success',
]);

after('deploy:failed', 'deploy:unlock');

<?php
namespace Deployer;

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/../contrib/rsync.php';

add('recipes', ['typo3_rsync']);

/**
 * DocumentRoot / WebRoot for the TYPO3 installation
 */
set('typo3_webroot', 'public');

/**
 * Path to TYPO3 cli
 */
set('bin/typo3', 'vendor/bin/typo3');

/**
 * Shared directories
 */
set('shared_dirs', [
    '{{typo3_webroot}}/fileadmin',
    '{{typo3_webroot}}/typo3temp',
    '{{typo3_webroot}}/uploads'
]);

/**
 * Shared files
 */
set('shared_files', [
    '{{typo3_webroot}}/.htaccess',
    'config/system/settings.php',
]);

/**
 * Writeable directories
 */
set('writable_dirs', [
    '{{typo3_webroot}}/fileadmin',
    'var',
]);

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
    'config/system/additional.php',
    'config/system/settings.php',
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
    'timeout' => 600
]);

desc('TYPO3 - Cache warmup for all caches');
task('typo3:cache:warmup', function () {
    cd('{{release_path}}');
    run('{{bin/php}} {{bin/typo3}} cache:warmup');
});

desc('TYPO3 - Cache clearing for all caches');
task('typo3:cache:flush', function () {
    cd('{{release_path}}');
    run('{{bin/php}} {{bin/typo3}} cache:flush');
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
task('deploy:update_code')->hidden()->disable();
task('deploy:info')->hidden()->disable();

desc('Deploys a TYPO3 project');
task('deploy', [
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'typo3:extension:setup',
    'typo3:cache:flush',
    'typo3:language:update',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:success'
]);

after('deploy:failed', 'deploy:unlock');

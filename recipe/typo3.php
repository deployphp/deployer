<?php

/**
 * TYPO3 Deployer Recipe
 *
 * Usage Examples:
 *
 * Deploy to production (using Git as source):
 *     vendor/bin/dep deploy production
 *
 * Deploy to staging using rsync:
 *     # In deploy.php or servers config, enable rsync
 *     set('use_rsync', true);
 *     vendor/bin/dep deploy staging
 *
 * Common TYPO3 commands:
 *     vendor/bin/dep typo3:cache:flush                     # Clear all TYPO3 caches
 *     vendor/bin/dep typo3:cache:warmup                    # Warmup system caches
 *     vendor/bin/dep typo3:language:update                 # Update extension language files
 *     vendor/bin/dep typo3:extension:setup                 # Set up all extensions
 *     vendor/bin/dep typo3:install:fixfolderstructure      # Automatically create required files and folders for TYPO3
 */

namespace Deployer;

require_once __DIR__ . '/common.php';
require_once 'contrib/rsync.php';

add('recipes', ['typo3']);

/**
 * Parse composer.json and return its contents as an array.
 * Used for auto-detecting TYPO3 settings like public_dir and bin_dir.
 */
set('composer_config', function () {
    return json_decode(file_get_contents('./composer.json'), true, 512, JSON_THROW_ON_ERROR);
});

/**
 * TYPO3 public (web) directory.
 * Automatically determined from composer.json.
 * Defaults to "public".
 */
set('typo3/public_dir', function () {
    $composerConfig = get('composer_config');

    if ($composerConfig['extra']['typo3/cms']['web-dir'] ?? false) {
        return $composerConfig['extra']['typo3/cms']['web-dir'];
    }

    return 'public';
});

/**
 * Path to the TYPO3 CLI binary.
 * Determined from composer.json "config.bin-dir" or defaults to "vendor/bin/typo3".
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
 * Directories that persist between releases.
 * Shared via symlinks from the shared/ directory.
 */
set('shared_dirs', [
    '{{typo3/public_dir}}/fileadmin',
    '{{typo3/public_dir}}/typo3temp/assets',
    'var/lock',
    'var/log',
    'var/session',
    'var/spool',
]);

/**
 * Files that persist between releases.
 * By default: config/system/settings.php
 */
if (!has('shared_files') || empty(get('shared_files'))) {
    set('shared_files', [
        'config/system/settings.php',
    ]);
}

/**
 * Writeable directories
 */
set('writable_dirs', [
    '{{typo3/public_dir}}/fileadmin',
    '{{typo3/public_dir}}/typo3temp/assets',
    'var/cache',
    'var/lock',
    'var/log',
]);

/**
 * Composer install options for production.
 */
set('composer_options', ' --no-dev --verbose --prefer-dist --no-progress --no-interaction --optimize-autoloader');

/**
 * If set in the config this recipe uses rsync.
 * Default setting: false (uses the Git repository)
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
    '/{{typo3/public_dir}}/fileadmin',
    '/{{typo3/public_dir}}/typo3temp',
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

/**
 * List of schema update types.
 * `safe` includes all necessary operations, to add or change fields or tables.
 */
set('typo3_updateschema_types', 'safe');


/**
 * TYPO3 Commands
 * All run via {{bin/php}} {{release_path}}/{{bin/typo3}} <command>
 */

desc('TYPO3 - Clear all caches');
task('typo3:cache:flush', function () {
    run('{{bin/php}} {{release_path}}/{{bin/typo3}} cache:flush');
});

desc('TYPO3 - Cache warmup for system caches');
task('typo3:cache:warmup', function () {
    run('{{bin/php}} {{release_path}}/{{bin/typo3}} cache:warmup --group system');
});

desc('TYPO3 - Update the language files of all activated extensions');
task('typo3:language:update', function () {
    run('{{bin/php}} {{release_path}}/{{bin/typo3}} language:update');
});

desc('TYPO3 - Set up all extensions');
task('typo3:extension:setup', function () {
    run('{{bin/php}} {{release_path}}/{{bin/typo3}} extension:setup');
});

desc('TYPO3 - Fix folder structure');
task('typo3:install:fixfolderstructure', function () {
    run('{{bin/php}} {{release_path}}/{{bin/typo3}} install:fixfolderstructure');
});

/**
 * Main deploy task for TYPO3.
 *
 * 1. Lock deploy to avoid concurrent runs
 * 2. Create release directory
 * 3. Update code (Git or rsync)
 * 4. Symlink shared dirs/files
 * 5. Fix TYPO3 folder structure
 * 6. Ensure writable dirs
 * 7. Run extension setup & perform schema updates
 * 8. Update language files
 * 9. Install composer vendors
 * 10. Flush caches
 * 11. Warm up TYPO3 caches
 * 12. Unlock and clean up
 */
desc('Deploys a TYPO3 project');
task('deploy', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'typo3:update_code',
    'deploy:shared',
    'typo3:install:fixfolderstructure',
    'deploy:writable',
    'typo3:extension:setup',
    'typo3:language:update',
    'deploy:vendors',
    'typo3:cache:flush',
    'typo3:cache:warmup',
    'deploy:publish',
]);

after('deploy:failed', 'deploy:unlock');

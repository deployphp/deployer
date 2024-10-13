<?php

namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['craftcms']);

set('log_files', 'storage/logs/*.log');

set('shared_dirs', [
    'storage',
    'web/assets',
]);

set('shared_files', ['.env']);

set('writable_dirs', [
    'config/project',
    'storage',
    'web/assets',
    'web/cpresources',
]);

/**
 * Run a craft command.
 *
 * Supported options:
 *  - 'showOutput': Show the output of the command if given.
 *  - 'interactive': Don't append the --interactive=0 flag to the command.
 *
 * @param string $command The craft command (with cli options if any).
 * @param array $options The options that define the behaviour of the command.
 *
 * @return callable A function that can be used as a task.
 */
function craft($command, $options = [])
{
    return function () use ($command, $options) {
        if (! test('[ -s {{release_path}}/.env ]')) {
            throw new \Exception('Your .env file is empty! Cannot proceed.');
        }

        // By default we don't want any command to be interactive
        if (! in_array('interactive', $options)) {
            $command .= ' --interactive=0';
        }

        $output = run("{{bin/php}} {{release_path}}/craft $command");

        if (in_array('showOutput', $options)) {
            writeln("<info>$output</info>");
        }
    };
}

/*
 * Migrations
 */

desc('Runs all pending Craft, plugin, and content migrations');
task('craft:migrate/all', craft('migrate/all'));

desc('Upgrades Craft by applying new migrations');
task('craft:migrate/up', craft('migrate/up'));

/*
 * Generate keys
 */

desc('Generates a new application ID and saves it in the `.env` file');
task('craft:setup/app-id', craft('setup/app-id'));

desc('Generates a new security key and saves it in the `.env` file');
task('craft:setup/security-key', craft('setup/security-key'));

/*
 * Project config
 */

desc('Applies project config file changes.');
task('craft:project-config/apply', craft('project-config/apply'));

/*
 * Caches
 */

desc('Flushes all caches registered in the system');
task('craft:cache/flush-all', craft('cache/flush-all'));

desc('Clear all caches');
task('craft:clear-caches/all', craft('clear-caches/all'));

desc('Clear all Asset caches');
task('craft:clear-caches/asset', craft('clear-caches/asset'));

desc('Clear all Asset indexing data');
task('craft:clear-caches/asset-indexing-data', craft('clear-caches/asset-indexing-data'));

desc('Clear all compiled classes');
task('craft:clear-caches/compiled-classes', craft('clear-caches/compiled-classes'));

desc('Clear all compiled templates');
task('craft:clear-caches/compiled-templates', craft('clear-caches/compiled-templates'));

desc('Clear all control panel resources');
task('craft:clear-caches/cp-resources', craft('clear-caches/cp-resources'));

desc('Clear all data caches');
task('craft:clear-caches/data', craft('clear-caches/data'));

desc('Clear all temp files');
task('craft:clear-caches/temp-files', craft('clear-caches/temp-files'));

/*
 * Garbage collection
 */

desc('Runs garbage collection');
task('craft:gc', craft('gc --delete-all-trashed=1 --silent-exit-on-exception=1', ['showOutput']));

/*
 * Main deploy
 */

desc('Deploys Craft CMS');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'craft:clear-caches/compiled-classes',
    'craft:migrate/all',
    'craft:project-config/apply',
    'craft:gc',
    'craft:clear-caches/all',
    'deploy:publish',
]);

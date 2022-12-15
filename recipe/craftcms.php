<?php

namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['craftcms']);

set('log_files', 'storage/logs/*.log');

set('shared_dirs', [
    'storage',
    'web/assets',
]);

set('writable_dirs', [
    'config/project',
    'storage',
    'web/assets',
    'web/cpresources',
]);

set('writable_mode', 'chmod');
set('writable_recursive', true);


/**
 * Run a craft command.
 *
 * Supported options:
 *
 * @param string $command The craft command (with cli options if any).
 * @param array $options The options that define the behaviour of the command.
 * @return callable A function that can be used as a task.
 */
function craft($command, $options = [])
{
    return function () use ($command, $options) {
        if (! test('[ -s {{release_path}}/.env ]')) {
            throw new \Exception('Your .env file is empty! Cannot proceed.');
        }

        try {
            $output = run("cd {{release_path}} && {{bin/php}} craft $command");

            if (in_array('showOutput', $options)) {
                writeln("<info>$output</info>");
            }
        } catch (\Throwable $e) {
            writeln("<error>{$e->getMessage()}</error>");
        }
    };
}

desc('Execute craft migrate/all');
task('craft:migrate/all', craft('migrate/all --interactive=0'))->once();

desc('Execute craft project-config/apply');
task('craft:project-config/apply', craft('project-config/apply --interactive=0'))->once();

desc('Execute craft clear-caches/all');
task('craft:clear-caches/all', craft('clear-caches/all --interactive=0'))->once();

desc('deploy');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'craft:clear-caches/all',
    'craft:migrate/all',
    'craft:project-config/apply',
    'deploy:publish',
]);


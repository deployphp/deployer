<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['laravel']);

set('shared_dirs', ['storage']);
set('shared_files', ['.env']);
set('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);
set('log_files', 'storage/logs/*.log');
set('laravel_version', function () {
    $result = run('{{bin/php}} {{release_path}}/artisan --version');
    preg_match_all('/(\d+\.?)+/', $result, $matches);
    return $matches[0][0] ?? 5.5;
});

/**
 * Run an artisan command.
 *
 * Supported options:
 * - 'min' => #.#: The minimum Laravel version required (included).
 * - 'max' => #.#: The maximum Laravel version required (included).
 * - 'skipIfNoEnv': Skip and warn the user if `.env` file is inexistant or empty.
 * - 'failIfNoEnv': Fail the command if `.env` file is inexistant or empty.
 * - 'runInCurrent': Run the artisan command in the current directory.
 * - 'showOutput': Show the output of the command if given.
 *
 * @param string $command The artisan command (with cli options if any).
 * @param array $options The options that define the behaviour of the command.
 * @return callable A function that can be used as a task.
 */
function artisan($command, $options = [])
{
    return function () use ($command, $options) {
        $versionTooEarly = array_key_exists('min', $options)
            && laravel_version_compare($options['min'], '<');

        $versionTooLate = array_key_exists('max', $options)
            && laravel_version_compare($options['max'], '>');

        if ($versionTooEarly || $versionTooLate) {
            return;
        }
        if (in_array('failIfNoEnv', $options) && !test('[ -s {{release_path}}/.env ]')) {
            throw new \Exception('Your .env file is empty! Cannot proceed.');
        }
        if (in_array('skipIfNoEnv', $options) && !test('[ -s {{release_path}}/.env ]')) {
            warning("Your .env file is empty! Skipping...</>");
            return;
        }

        $artisan = in_array('runInCurrent', $options)
            ? '{{current_path}}/artisan'
            : '{{release_path}}/artisan';

        $output = run("{{bin/php}} $artisan $command");

        if (in_array('showOutput', $options)) {
            writeln("<info>$output</info>");
        }
    };
}

function laravel_version_compare($version, $comparator)
{
    return version_compare(get('laravel_version'), $version, $comparator);
}

desc('Disable maintenance mode');
task('artisan:up', artisan('up', ['runInCurrent', 'showOutput']));

desc('Enable maintenance mode');
task('artisan:down', artisan('down', ['runInCurrent', 'showOutput']));

desc('Execute artisan migrate');
task('artisan:migrate', artisan('migrate --force', ['skipIfNoEnv']))->once();

desc('Execute artisan migrate:fresh');
task('artisan:migrate:fresh', artisan('migrate:fresh --force'));

desc('Execute artisan migrate:rollback');
task('artisan:migrate:rollback', artisan('migrate:rollback --force', ['showOutput']));

desc('Execute artisan migrate:status');
task('artisan:migrate:status', artisan('migrate:status', ['showOutput']));

desc('Execute artisan db:seed');
task('artisan:db:seed', artisan('db:seed --force', ['showOutput']));

desc('Execute artisan cache:clear');
task('artisan:cache:clear', artisan('cache:clear'));

desc('Execute artisan config:clear');
task('artisan:config:clear', artisan('config:clear'));

desc('Execute artisan config:cache');
task('artisan:config:cache', artisan('config:cache'));

desc('Execute artisan route:cache');
task('artisan:route:cache', artisan('route:cache'));

desc('Execute artisan view:clear');
task('artisan:view:clear', artisan('view:clear'));

desc('Execute artisan view:cache');
task('artisan:view:cache', artisan('view:cache', ['min' => 5.6]));

desc('Execute artisan optimize');
task('artisan:optimize', artisan('optimize', ['max' => 5.7]));

desc('Execute artisan optimize:clear');
task('artisan:optimize:clear', artisan('optimize:clear', ['min' => 5.7]));

desc('Execute artisan queue:restart');
task('artisan:queue:restart', artisan('queue:restart'));

desc('Execute artisan storage:link');
task('artisan:storage:link', artisan('storage:link', ['min' => 5.3]));

desc('Execute artisan horizon:assets');
task('artisan:horizon:assets', artisan('horizon:assets'));

desc('Execute artisan horizon:publish');
task('artisan:horizon:publish', artisan('horizon:publish'));

desc('Execute artisan horizon:terminate');
task('artisan:horizon:terminate', artisan('horizon:terminate'));

desc('Execute artisan telescope:clear');
task('artisan:telescope:clear', artisan('telescope:clear'));

desc('Execute artisan telescope:prune');
task('artisan:telescope:prune', artisan('telescope:prune'));

desc('Execute artisan telescope:publish');
task('artisan:telescope:publish', artisan('telescope:publish'));

desc('Execute artisan nova:publish');
task('artisan:nova:publish', artisan('nova:publish'));

desc('Execute artisan event:clear');
task('artisan:event:clear', artisan('event:clear', ['min' => '5.8.9']));

desc('Execute artisan event:cache');
task('artisan:event:cache', artisan('event:cache', ['min' => '5.8.9']));

/**
 * Task deploy:public_disk support the public disk.
 * To run this task automatically, please add below line to your deploy.php file
 *
 *     before('deploy:symlink', 'deploy:public_disk');
 *
 * [Laravel filesystem configuration](https://laravel.com/docs/5.2/filesystem#configuration)
 */
desc('Make symlink for public disk');
task('deploy:public_disk', function () {
    // Remove from source.
    run('if [ -d $(echo {{release_path}}/public/storage) ]; then rm -rf {{release_path}}/public/storage; fi');

    // Create shared dir if it does not exist.
    run('mkdir -p {{deploy_path}}/shared/storage/app/public');

    // Symlink shared dir to release dir
    run('{{bin/symlink}} {{deploy_path}}/shared/storage/app/public {{release_path}}/public/storage');
});

/**
 * Main deploy task.
 */
desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'artisan:storage:link',
    'artisan:view:cache',
    'artisan:config:cache',
    'deploy:publish',
]);

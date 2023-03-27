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
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);
set('log_files', 'storage/logs/*.log');
set('laravel_version', function () {
    $result = run('{{bin/php}} {{release_or_current_path}}/artisan --version');
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
 * - 'showOutput': Show the output of the command if given.
 *
 * @param string $command The artisan command (with cli options if any).
 * @param array $options The options that define the behaviour of the command.
 * @return callable A function that can be used as a task.
 */
function artisan($command, $options = [])
{
    return function () use ($command, $options) {

        // Ensure the artisan command is available on the current version.
        $versionTooEarly = array_key_exists('min', $options)
            && laravel_version_compare($options['min'], '<');

        $versionTooLate = array_key_exists('max', $options)
            && laravel_version_compare($options['max'], '>');

        if ($versionTooEarly || $versionTooLate) {
            return;
        }

        // Ensure we warn or fail when a command relies on the ".env" file.
        if (in_array('failIfNoEnv', $options) && !test('[ -s {{release_or_current_path}}/.env ]')) {
            throw new \Exception('Your .env file is empty! Cannot proceed.');
        }

        if (in_array('skipIfNoEnv', $options) && !test('[ -s {{release_or_current_path}}/.env ]')) {
            warning("Your .env file is empty! Skipping...</>");
            return;
        }

        $artisan = '{{release_or_current_path}}/artisan';

        // Run the artisan command.
        $output = run("{{bin/php}} $artisan $command");

        // Output the results when appropriate.
        if (in_array('showOutput', $options)) {
            writeln("<info>$output</info>");
        }
    };
}

function laravel_version_compare($version, $comparator)
{
    return version_compare(get('laravel_version'), $version, $comparator);
}

/*
 * Maintenance mode.
 */

desc('Puts the application into maintenance / demo mode');
task('artisan:down', artisan('down', ['showOutput']));

desc('Brings the application out of maintenance mode');
task('artisan:up', artisan('up', ['showOutput']));

/*
 * Generate keys.
 */

desc('Sets the application key');
task('artisan:key:generate', artisan('key:generate'));

desc('Creates the encryption keys for API authentication');
task('artisan:passport:keys', artisan('passport:keys'));

/*
 * Database and migrations.
 */

desc('Seeds the database with records');
task('artisan:db:seed', artisan('db:seed --force', ['skipIfNoEnv', 'showOutput']));

desc('Runs the database migrations');
task('artisan:migrate', artisan('migrate --force', ['skipIfNoEnv']));

desc('Drops all tables and re-run all migrations');
task('artisan:migrate:fresh', artisan('migrate:fresh --force', ['skipIfNoEnv']));

desc('Rollbacks the last database migration');
task('artisan:migrate:rollback', artisan('migrate:rollback --force', ['skipIfNoEnv', 'showOutput']));

desc('Shows the status of each migration');
task('artisan:migrate:status', artisan('migrate:status', ['skipIfNoEnv', 'showOutput']));

/*
 * Cache and optimizations.
 */

desc('Flushes the application cache');
task('artisan:cache:clear', artisan('cache:clear'));

desc('Creates a cache file for faster configuration loading');
task('artisan:config:cache', artisan('config:cache'));

desc('Removes the configuration cache file');
task('artisan:config:clear', artisan('config:clear'));

desc('Discovers and cache the application\'s events and listeners');
task('artisan:event:cache', artisan('event:cache', ['min' => '5.8.9']));

desc('Clears all cached events and listeners');
task('artisan:event:clear', artisan('event:clear', ['min' => '5.8.9']));

desc('Lists the application\'s events and listeners');
task('artisan:event:list', artisan('event:list', ['showOutput', 'min' => '5.8.9']));

desc('Cache the framework bootstrap files');
task('artisan:optimize', artisan('optimize'));

desc('Removes the cached bootstrap files');
task('artisan:optimize:clear', artisan('optimize:clear'));

desc('Creates a route cache file for faster route registration');
task('artisan:route:cache', artisan('route:cache'));

desc('Removes the route cache file');
task('artisan:route:clear', artisan('route:clear'));

desc('Lists all registered routes');
task('artisan:route:list', artisan('route:list', ['showOutput']));

desc('Creates the symbolic links configured for the application');
task('artisan:storage:link', artisan('storage:link', ['min' => 5.3]));

desc('Compiles all of the application\'s Blade templates');
task('artisan:view:cache', artisan('view:cache', ['min' => 5.6]));

desc('Clears all compiled view files');
task('artisan:view:clear', artisan('view:clear'));

/**
 * Queue and Horizon.
 */

desc('Lists all of the failed queue jobs');
task('artisan:queue:failed', artisan('queue:failed', ['showOutput']));

desc('Flushes all of the failed queue jobs');
task('artisan:queue:flush', artisan('queue:flush'));

desc('Restarts queue worker daemons after their current job');
task('artisan:queue:restart', artisan('queue:restart'));

desc('Starts a master supervisor in the foreground');
task('artisan:horizon', artisan('horizon'));

desc('Deletes all of the jobs from the specified queue');
task('artisan:horizon:clear', artisan('horizon:clear --force'));

desc('Instructs the master supervisor to continue processing jobs');
task('artisan:horizon:continue', artisan('horizon:continue'));

desc('Lists all of the deployed machines');
task('artisan:horizon:list', artisan('horizon:list', ['showOutput']));

desc('Pauses the master supervisor');
task('artisan:horizon:pause', artisan('horizon:pause'));

desc('Terminates any rogue Horizon processes');
task('artisan:horizon:purge', artisan('horizon:purge'));

desc('Gets the current status of Horizon');
task('artisan:horizon:status', artisan('horizon:status', ['showOutput']));

desc('Terminates the master supervisor so it can be restarted');
task('artisan:horizon:terminate', artisan('horizon:terminate'));

desc('Publish all of the Horizon resources');
task('artisan:horizon:publish', artisan('horizon:publish'));

/*
 * Telescope.
 */

desc('Clears all entries from Telescope');
task('artisan:telescope:clear', artisan('telescope:clear'));

desc('Prunes stale entries from the Telescope database');
task('artisan:telescope:prune', artisan('telescope:prune'));

/*
 * Octane.
 */

desc('Starts the octane server');
task('artisan:octane', artisan('octane:start'));

desc('Reloads the octane server');
task('artisan:octane:reload', artisan('octane:reload'));

desc('Stops the octane server');
task('artisan:octane:stop', artisan('octane:stop'));

desc('Check the status of the octane server');
task('artisan:octane:status', artisan('octane:status'));

/*
 * Nova.
 */

desc('Publish all of the Laravel Nova resources');
task('artisan:nova:publish', artisan('nova:publish'));

/**
 * Main deploy task.
 */
desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'artisan:storage:link',
    'artisan:config:cache',
    'artisan:route:cache',
    'artisan:view:cache',
    'artisan:event:cache',
    'artisan:migrate',
    'deploy:publish',
]);

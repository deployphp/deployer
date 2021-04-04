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
    $result = run('{{bin/php}} {{release_or_current_path}}/artisan --version');
    preg_match_all('/(\d+\.?)+/', $result, $matches);
    return $matches[0][0] ?? 5.5;
});

$artisanCommands = [
    // Maintenance mode.
    'up' => ['Bring the application out of maintenance mode', 'runInCurrent', 'showOutput'],
    'down' => ['Put the application into maintenance / demo mode', 'runInCurrent', 'showOutput'],

    // Database and migrations.
    'migrate' => ['Run the database migrations', 'skipIfNoEnv', 'with' => '--force'],
    'migrate:fresh' => ['Drop all tables and re-run all migrations', 'with' => '--force'],
    'migrate:rollback' => ['Rollback the last database migration', 'showOutput', 'with' => '--force'],
    'migrate:status' => ['Show the status of each migration', 'showOutput'],
    'db:seed' => ['Seed the database with records', 'showOutput', 'with' => '--force'],

    // Cache and optimizations.
    'cache:clear' => ['Flush the application cache'],
    'config:cache' => ['Create a cache file for faster configuration loading'],
    'config:clear' => ['Remove the configuration cache file'],
    'event:cache' => ['Discover and cache the application\'s events and listeners', 'min' => '5.8.9'],
    'event:clear' => ['Clear all cached events and listeners', 'min' => '5.8.9'],
    'event:list' => ['List the application\'s events and listeners', 'showOutput', 'min' => '5.8.9'],
    'optimize'  => ['Cache the framework bootstrap files'],
    'optimize:clear' => ['Remove the cached bootstrap files'],
    'route:cache' => ['Create a route cache file for faster route registration'],
    'route:clear' => ['Remove the route cache file'],
    'route:list' => ['List all registered routes', 'showOutput'],
    'storage:link' => ['Create the symbolic links configured for the application', 'min' => 5.3],
    'view:cache' => ['Compile all of the application\'s Blade templates', 'min' => 5.6],
    'view:clear' => ['Clear all compiled view files'],

    // Queue and Horizon.
    'queue:failed' => ['List all of the failed queue jobs', 'showOutput'],
    'queue:flush' => ['Flush all of the failed queue jobs'],
    'queue:restart' => ['Restart queue worker daemons after their current job'],
    'horizon' => ['Start a master supervisor in the foreground'],
    'horizon:clear' => ['Delete all of the jobs from the specified queue', 'with' => '--force'],
    'horizon:continue' => ['Instruct the master supervisor to continue processing jobs'],
    'horizon:list' => ['List all of the deployed machines', 'showOutput'],
    'horizon:pause' => ['Pause the master supervisor'],
    'horizon:purge' => ['Terminate any rogue Horizon processes'],
    'horizon:status' => ['Get the current status of Horizon', 'showOutput'],
    'horizon:terminate' => ['Terminate the master supervisor so it can be restarted'],

    // Telescope.
    'telescope:clear' => ['Clear all entries from Telescope'],
    'telescope:prune' => ['Prune stale entries from the Telescope database'],
];

// Register all artisan commands.
foreach ($artisanCommands as $command => $options) {
    $description = array_shift($options);
    $parameters = isset($options['with']) ? (' ' . $options['with']) : '';

    desc($description);
    task("artisan:$command", artisan($command . $parameters, $options));
}

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

        // Use the release_path by default unless it does not exist or specified otherwise.
        $artisan = in_array('runInCurrent', $options)
            ? '{{current_path}}/artisan'
            : '{{release_or_current_path}}/artisan';

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

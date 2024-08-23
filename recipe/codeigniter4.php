<?php

namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['codeigniter4']);

// Default Configurations
set('public_path', 'public');

set('shared_dirs', ['writable']);

set('shared_files', ['.env']);

set('writable_dirs', [
    'writable/cache',
    'writable/debugbar',
    'writable/logs',
    'writable/session',
    'writable/uploads'
]);

set('log_files', 'writable/logs/*.log');

set('codeigniter4_version', function () {
    $result = run('{{bin/php}} {{release_or_current_path}}/spark');
    preg_match_all('/(\d+\.?)+/', $result, $matches);
    return $matches[0][0] ?? 5.5;
});

/**
 * Run an spark command.
 *
 * Supported options:
 * - 'min' => #.#: The minimum Codeigniter4 version required (included).
 * - 'max' => #.#: The maximum Codeigniter4 version required (included).
 * - 'skipIfNoEnv': Skip and warn the user if `.env` file is inexistent or empty.
 * - 'failIfNoEnv': Fail the command if `.env` file is inexistent or empty.
 * - 'showOutput': Show the output of the command if given.
 *
 * @param string $command The spark command (with cli options if any).
 * @param array $options The options that define the behavior of the command.
 * @return callable A function that can be used as a task.
 */
function spark($command, $options = [])
{
    return function () use ($command, $options) {

        // Ensure the spark command is available on the current version.
        $versionTooEarly = array_key_exists('min', $options)
            && codeigniter4_version_compare($options['min'], '<');

        $versionTooLate = array_key_exists('max', $options)
            && codeigniter4_version_compare($options['max'], '>');

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

        $spark = '{{release_or_current_path}}/spark';

        // Run the spark command.
        $output = run("{{bin/php}} $spark $command");

        // Output the results when appropriate.
        if (in_array('showOutput', $options)) {
            writeln("<info>$output</info>");
        }
    };
}

function codeigniter4_version_compare($version, $comparator)
{
    return version_compare(get('codeigniter4_version'), $version, $comparator);
}


/**
 * Discover & Checks
 */

desc('Shows file cache information in the current system.');
task('spark:cache:info', spark('cache:info', ['showOutput']));

desc('Check your Config values.');
task('spark:config:check', spark('config:check', ['skipIfNoEnv', 'showOutput', 'min' => '4.5.0']));

desc('Retrieves the current environment, or set a new one.');
task('spark:env', spark('env', ['skipIfNoEnv', 'showOutput']));

desc('Check filters for a route.');
task('spark:filter:check', spark('filter:check', ['showOutput', 'min' => '4.3.0']));

desc('Find and save available phrases to translate.');
task('spark:lang:find', spark('lang:find', ['showOutput', 'min' => '4.5.0']));

desc('Verifies your namespaces are setup correctly.');
task('spark:namespaces', spark('namespaces', ['showOutput']));

desc('Check your php.ini values.');
task('spark:phpini:check', spark('phpini:check', ['showOutput', 'min' => '4.5.0']));

desc('Displays all routes.');
task('spark:routes', spark('routes', ['showOutput', 'min' => '4.3.0']));


/**
 * Actions
 */

desc('Generates a new encryption key and writes it in an `.env` file.');
task('spark:key:generate', spark('key:generate', ['skipIfNoEnv']));

desc('Optimize for production.');
task('spark:optimize', spark('optimize', ['min' => '4.5.0']));

desc('Discovers and executes all predefined Publisher classes.');
task('spark:publish', spark('publish', ['skipIfNoEnv', 'showOutput']));


/*
 * Database and migrations.
 */

desc('Create a new database schema.');
task('spark:db:create', spark('db:create', ['showOutput']));

desc('Runs the specified seeder to populate known data into the database.');
task('spark:db:seed', spark('db:seed', ['skipIfNoEnv']));

desc('Retrieves information on the selected table.');
task('spark:db:table', spark('db:table', ['skipIfNoEnv', 'showOutput', 'min' => '4.5.0']));

desc('Locates and runs all new migrations against the database.');
task('spark:migrate', spark('migrate --all', ['skipIfNoEnv']));

desc('Does a rollback followed by a latest to refresh the current state of the database.');
task('spark:migrate:refresh', spark('migrate:refresh -f --all', ['skipIfNoEnv']));

desc('Runs the "down" method for all migrations in the last batch.');
task('spark:migrate:rollback', spark('migrate:rollback -f', ['skipIfNoEnv', 'showOutput']));

desc('Displays a list of all migrations and whether they\'ve been run or not.');
task('spark:migrate:status', spark('migrate:status', ['skipIfNoEnv', 'showOutput']));


/**
 * Housekeeping
 */

desc('Clears the current system caches.');
task('spark:cache:clear', spark('cache:clear'));

desc('Clears all debugbar JSON files.');
task('spark:debugbar:clear', spark('debugbar:clear'));

desc('Clears all log files.');
task('spark:logs:clear', spark('logs:clear'));


/**
 * Custom Spark Command for shield or setting packages
 */
desc('Run a custom spark command.');
task('spark:custom', spark('', ['showOutput']));



/**
 * Main deploy task.
 */
desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'spark:optimize',
    'spark:migrate',
    'deploy:publish',
]);

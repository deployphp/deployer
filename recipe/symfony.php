<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';


/**
 * Symfony Configuration
 */

// Symfony build env
env('env', 'prod');

// Symfony shared dirs
set('shared_dirs', ['app/logs']);

// Symfony shared files
set('shared_files', ['app/config/parameters.yml']);

// Symfony writable dirs
set('writable_dirs', ['app/cache', 'app/logs']);

// Assets
set('assets', ['web/css', 'web/images', 'web/js']);

// Removable files
set('removable_files', ['web/app_*.php', 'web/config.php']);

// Requires non symfony-core package `kriswallsmith/assetic` to be installed
set('dump_assets', false);

// Environment vars
env('env_vars', 'SYMFONY_ENV={{env}}');

// Adding support for the Symfony3 directory structure
set('bin_dir', 'app');
set('var_dir', 'app');

// Symfony console bin
env('bin/console', function () {
    return sprintf('{{release_path}}/%s/console', trim(get('bin_dir'), '/'));
});

// Symfony console opts
env('console_options', function () {
    $options = '--no-interaction --env={{env}}';

    return env('env') !== 'prod' ? $options : sprintf('%s --no-debug', $options);
});

// Ask questions?
env('interaction', true);

// Database strategy configuration
env('enable_database_create', false);
env('use_database_migration_strategy', false);
// Doctrine migration files path
env('doctrine_migration_path', 'app/DoctrineMigrations');

/**
 * Create cache dir
 */
task('deploy:create_cache_dir', function () {
    // Set cache dir
    env('cache_dir', '{{release_path}}/' . trim(get('var_dir'), '/') . '/cache');

    // Remove cache dir if it exist
    run('if [ -d "{{cache_dir}}" ]; then rm -rf {{cache_dir}}; fi');

    // Create cache dir
    run('mkdir -p {{cache_dir}}');

    // Set rights
    run("chmod -R g+w {{cache_dir}}");
})->desc('Create cache dir');


/**
 * Normalize asset timestamps
 */
task('deploy:assets', function () {
    $assets = implode(' ', array_map(function ($asset) {
        return "{{release_path}}/$asset";
    }, get('assets')));

    run(sprintf('find %s -exec touch -t %s {} \';\' &> /dev/null || true', $assets, date('Ymdhi.s')));
})->desc('Normalize asset timestamps');


/**
 * Install assets from public dir of bundles
 */
task('deploy:assets:install', function () {
    run('{{env_vars}} {{bin/php}} {{bin/console}} assets:install {{console_options}} {{release_path}}/web');
})->desc('Install bundle assets');


/**
 * Dump all assets to the filesystem
 */
task('deploy:assetic:dump', function () {
    if (get('dump_assets')) {
        run('{{env_vars}} {{bin/php}} {{bin/console}} assetic:dump {{console_options}}');
    }
})->desc('Dump assets');


/**
 * Warm up cache
 */
task('deploy:cache:warmup', function () {
    run('{{env_vars}} {{bin/php}} {{bin/console}} cache:warmup {{console_options}}');
})->desc('Warm up cache');

/**
 * Create database if not exists
 */
task('database:create', function () {
    if (env('enable_database_create')) {
        run('{{env_vars}} {{bin/php}} {{bin/console}} doctrine:database:create --if-not-exists {{console_options}}');
    }
})->desc('Create database if not exists (only if set `enable_database_create` true)');

/**
 * Migrate database
 */
task('database:migrate', function () {
    if (env('use_database_migration_strategy')) {
        run('{{env_vars}} {{bin/php}} {{bin/console}} doctrine:migrations:migrate {{console_options}}');
    }
})->desc('Migrate database (only if set `use_database_migration_strategy` true)');

/**
 * Database migration rollback
 */
task('database:migrate:rollback', function () {
    if (env('use_database_migration_strategy')) {
        $releases = env('releases_list');

        if (isset($releases[1])) {
            $commandPattern = 'cd {{deploy_path}}/releases/%s && find {{doctrine_migration_path}} -maxdepth 1 -mindepth 1 -type f -name \'Version*.php\'';
            $currentMigrations = run(sprintf($commandPattern, $releases[0]))->toArray();
            $prevMigrations = run(sprintf($commandPattern, $releases[1]))->toArray();

            $downDiffMigrations = array_diff($currentMigrations, $prevMigrations);
            arsort($downDiffMigrations);

            switch (count($downDiffMigrations)) {
                case 0:
                    writeln('There isn\'t deprecated database migration.');
                    break;
                case 1:
                    writeln('There is <comment>1</comment> deprecated database migration!');
                    break;
                default:
                    writeln(
                        sprintf(
                            'There are <comment>%d</comment> deprecated database migrations!',
                            count($downDiffMigrations)
                        )
                    );
            }

            foreach ($downDiffMigrations as $migrationFile) {
                if (preg_match('|Version(\d+)\.php|', $migrationFile, $matches)) {
                    if (isVerbose()) {
                        writeln(sprintf('Start down the <comment>%s</comment> migration file.', $matches[0]));
                    }
                    run(
                        sprintf(
                            '{{env_vars}} {{bin/php}} %s/%s/console doctrine:migrations:execute %s --down {{console_options}}',
                            "{{deploy_path}}/releases/{$releases[0]}",
                            trim(get('bin_dir'), '/'),
                            $matches[1]
                        )
                    );
                } else {
                    throw new \Exception(sprintf('Invalid migration file name: `%s`', $migrationFile));
                }
            }

            if (count($downDiffMigrations) > 0) {
                writeln(sprintf('Undo <comment>%d</comment> migrations file: done', count($downDiffMigrations)));
            }

            run(
                sprintf(
                    '{{env_vars}} {{bin/php}} %s/%s/console doctrine:migrations:migrate {{console_options}}',
                    "{{deploy_path}}/releases/{$releases[1]}",
                    trim(get('bin_dir'), '/')
                )
            );
        }
    }
})->desc('Rollback the database (only if set `use_database_migration_strategy` true)');

// Run before rollback
before('rollback', 'database:migrate:rollback');

/**
 * Remove app_dev.php files
 */
task('deploy:clear_controllers', function () {
    foreach (get('removable_files') as $file) {
        run('rm -f {{release_path}}/' . $file);
    }
})->setPrivate();

// Run after code is checked out
after('deploy:update_code', 'deploy:clear_controllers');

/**
 * Init parameters.yml
 *
 * Ask parameters from all dist file (read dist files from composer.json) .
 */
task('deploy:init-parameters-yml', function () {
    if (env('interaction')) {
        $composerConfigString = run('cat {{release_path}}/composer.json');
        if ($composerConfigString->toString()) {
            $composerConfig = json_decode($composerConfigString, true);
            $distFiles = isset($composerConfig['extra']['incenteev-parameters'])
                ? $composerConfig['extra']['incenteev-parameters']
                : []
            ;

            /** @var string|array $distFile */
            foreach ($distFiles as $distFile) {
                // get filename
                $distFile = is_array($distFile) ? $distFile['file'] : $distFile;
                env('config_yml_path', '{{release_path}}/' . $distFile);

                $result = run('if [[ -f {{config_yml_path}} && ! -s {{config_yml_path}} ]] ; then echo "1" ; else echo "0" ; fi');
                if ($result->toString() == '1') {
                    writeln(sprintf('Set the `%s` config file parameters', $distFile));
                    $ymlParser = new \Symfony\Component\Yaml\Parser();
                    $parameters = $ymlParser->parse((string) run('cat {{config_yml_path}}.dist'));
                    $newParameters = [];
                    foreach ($parameters['parameters'] as $key => $default) {
                        $value = ask($key, $default);
                        $newParameters[$key] = $value;
                    }
                    $ymlDumper = new \Symfony\Component\Yaml\Dumper();
                    $content = $ymlDumper->dump(['parameters' => $newParameters], 2);
                    run("cat << EOYAML > {{config_yml_path}}\n$content\nEOYAML");
                }
            }
        }
    }

})->desc('Initialize `parameters.yml`');

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:create_cache_dir',
    'deploy:shared',
    'deploy:assets',
    'deploy:init-parameters-yml',
    'deploy:vendors',
    'deploy:assets:install',
    'deploy:assetic:dump',
    'deploy:cache:warmup',
    'deploy:writable',
    'database:create',
    'database:migrate',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy your project');

// Display success message on completion
after('deploy', 'success');

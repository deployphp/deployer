<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/common.php';

/**
 * Create cache dir
 */
task('deploy:create_cache_dir', function () {
    $releasePath = env()->getReleasePath();

    // Set cache dir
    env()->set('cache_dir', $cacheDir = "$releasePath/app/cache");

    // Remove cache dir if it exist
    run("if [ -d \"$cacheDir\" ]; then rm -rf $cacheDir; fi");

    // Create cache dir
    run("mkdir -p $cacheDir");
})->desc('Creating cache dir');

after('deploy:update_code', 'deploy:create_cache_dir');

/**
 * Set right permissions
 */
task('deploy:permissions:setfacl', function () {
    $user = config()->getUser();
    $wwwUser = config()->getWwwUser();
    $releasePath = env()->getReleasePath();

    $dirs = (array)get('writeable_dirs', []);

    $run = run("if which setfacl; then echo \"ok\"; fi");
    if (empty($run)) {
        writeln('<comment>Enable ACL support and install "setfacl"</comment>');
        return;
    }

    cd($releasePath);

    foreach ($dirs as $dir) {
        run("setfacl -R -m u:$wwwUser:rwX -m u:$user:rwX $dir");
        run("setfacl -dR -m u:$wwwUser:rwX -m u:$user:rwX $dir");
    }
})->desc('Setting permissions');


/**
 * Normalize asset timestamps
 */
task('deploy:assets', function () {
    $releasePath = env()->getReleasePath();

    $assets = get('assets', []);

    $assets = array_map(function ($asset) use ($releasePath) {
        return "$releasePath/$asset";
    }, $assets);
    $assets = implode(' ', $assets);

    $time = date('Ymdhi.s');

    run("find $assets -exec touch -t $time {} ';' &> /dev/null || true");
})->desc('Normalizing asset timestamps');


/**
 * Dump all assets to the filesystem
 */
task('deploy:assetic:dump', function () {
    $releasePath = env()->getReleasePath();
    $prod = get('env', 'prod');

    run("php $releasePath/app/console assetic:dump --env=$prod --no-debug");

})->desc('Dumping assets');


/**
 * Warm up cache
 */
task('deploy:cache:warmup', function () {
    $releasePath = env()->getReleasePath();
    $cacheDir = env()->get('cache_dir', "$releasePath/app/cache");

    $prod = get('env', 'prod');

    run("php $releasePath/app/console cache:warmup  --env=$prod --no-debug");

    run("chmod -R g+w $cacheDir");

})->desc('Warming up cache');


/**
 * Migrate database
 */
task('database:migrate', function () {
    $releasePath = env()->getReleasePath();
    $prod = get('env', 'prod');
    $serverName = config()->getName();

    $run = get('auto_migrate', false);

    if (output()->isVerbose()) {
        $run = askConfirmation("Run migrations on $serverName server?", $run);
    }

    if ($run) {
        run("php $releasePath/app/console doctrine:migrations:migrate --env=$prod --no-debug --no-interaction");
    }

})->desc('Migrating database');


/**
 * Remove app_dev.php files
 */
task('deploy:clear_controllers', function () {
    $releasePath = env()->getReleasePath();

    run("rm -f $releasePath/web/app_*.php");
});

after('deploy:update_code', 'deploy:clear_controllers');

// Symfony Assets
set('assets', ['web/css', 'web/images', 'web/js']);

// Symfony shared dirs
set('shared_dirs', ['app/logs']);

// Symfony shared files
set('shared_files', ['app/config/parameters.yml']);

// Symfony writeable dirs
set('writeable_dirs', ['app/cache', 'app/logs']);


/**
 * Main task
 */
task('deploy', [
    'deploy:start',
    'deploy:prepare',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writeable_dirs',
    'deploy:assets',
    'deploy:vendors',
    'deploy:assetic:dump',
    'database:migrate',
    'deploy:cache:warmup',
    'deploy:symlink',
    'cleanup',
    'deploy:end'
])->desc('Deploy your project');


/**
 * Success message
 */
after('deploy', function () {
    $host = config()->getHost();
    writeln("<info>Successfully deployed on</info> <fg=cyan>$host</fg=cyan>");
});


 
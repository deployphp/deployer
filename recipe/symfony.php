<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Rollback to previous release.
 */
task('rollback', function () {
    $basePath = config()->getPath();

    $releases = env()->releases();

    if (count($releases) >= 2) {
        $releaseDir = "releases/" . $releases[1];
        run("rm -f current");
        run("ln -s $basePath/$releaseDir current");
    } else {
        writeln("<comment>No more releases you can revert to.</comment>");
    }
})->desc('Rollback to previous release');


/**
 * Preparing server for deployment.
 */
task('deploy:prepare', function () {
    $basePath = config()->getPath();

    // Check if base path exist.
    run("if [ ! -d \"$basePath\" ]; then mkdir $basePath; fi", true);

    // Create releases dir.
    run("if [ ! -d \"releases\" ]; then mkdir releases; fi");

    // Create shared dir.
    run("if [ ! -d \"shared\" ]; then mkdir shared; fi");
})->desc('Preparing server for deploy');


/**
 * Update project code
 */
task('deploy:update_code', function () {
    $basePath = config()->getPath();
    $repository = get('repository', false);

    if (false === $repository) {
        throw new \RuntimeException('You have to specify repository.');
    }

    $release = date('Ymd') . substr((string)time(), -5);
    $releasePath = "$basePath/releases/$release";

    env()->setRelease($release);
    env()->setReleasePath($releasePath);

    run("git clone -q $repository $releasePath");
    run("chmod -R g+w $releasePath");
})->desc('Updating code');


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
 * Create symlinks for shared directories and files
 */
task('deploy:shared', function () {
    $basePath = config()->getPath();
    $sharedPath = "$basePath/shared";
    $releasePath = env()->getReleasePath();

    // User specified shared directories
    $sharedDirs = (array)get('shared_dirs', ['app/logs']);

    foreach ($sharedDirs as $dir) {
        // Remove dir from source
        run("if [ -d \"$releasePath/$dir\" ]; then rm -rf $releasePath/$dir; fi");

        // Create shared dir if does not exist
        run("mkdir -p $sharedPath/$dir");

        // Symlink shared dir to release dir
        run("ln -nfs $sharedPath/$dir $releasePath/$dir");
    }

    // User specified shared files
    $sharedFiles = (array)get('shared_dirs', ['app/config/parameters.yml']);

    foreach ($sharedFiles as $file) {
        // Create dir of shared file
        run("mkdir -p $sharedPath/" . dirname($file));

        // Touch shared file
        run("touch $sharedPath/$file");

        // Symlink shared file to release file
        run("ln -nfs $sharedPath/$file $releasePath/$file");
    }
})->desc('Creating symlinks for shared files');


/**
 * Make writeable dirs
 */
task('deploy:writeable_dirs', function () {
    $user = config()->getUser();
    $wwwUser = config()->getWwwUser();
    $releasePath = env()->getReleasePath();

    cd($releasePath);

    // User specified writeable dirs
    $dirs = (array)get('writeable_dirs', ['app/cache', 'app/logs']);

    foreach ($dirs as $dir) {
        run("chmod -R 0777 $dir");
        run("chmod -R g+w $dir");
    }
})->desc('Make writeable dirs');


/**
 * Set right permissions
 */
task('deploy:permissions:setfacl', function () {
    $user = config()->getUser();
    $wwwUser = config()->getWwwUser();
    $releasePath = env()->getReleasePath();

    $dirs = (array)get('writeable_dirs', ['app/cache', 'app/logs']);

    if (empty(run("if which setfacl; then echo \"ok\"; fi"))) {
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

    $assets = get('assets', ['web/css', 'web/images', 'web/js']);

    $assets = array_map(function ($asset) use ($releasePath) {
        return "$releasePath/$asset";
    }, $assets);
    $assets = implode(' ', $assets);

    $time = date('Ymdhi.s');

    run("find $assets -exec touch -t $time {} ';' &> /dev/null || true");
})->desc('Normalizing asset timestamps');


/**
 * Install vendors
 */
task('deploy:vendors', function () {
    $releasePath = env()->getReleasePath();

    cd($releasePath);
    $prod = get('env', 'prod');
    $isComposer = run("if [ -e $releasePath/composer.phar ]; then echo 'true'; fi");

    if ('true' !== $isComposer) {
        run("curl -s http://getcomposer.org/installer | php");
    }

    run("SYMFONY_ENV=$prod php composer.phar install --no-dev --verbose --prefer-dist --optimize-autoloader --no-progress");

})->desc('Installing vendors');


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


/**
 * Create symlink to last release
 */
task('deploy:symlink', function () {
    $releasePath = env()->getReleasePath();
    $releaseDir = get('release_dir', null);

    run("rm -f current && ln -s $releasePath current");

})->desc('Creating symlink to release');


/**
 * Cleanup old releases
 */
task('cleanup', function () {
    $releases = env()->releases();

    $keep = get('keep_releases', 3);

    while ($keep > 0) {
        array_shift($releases);
        --$keep;
    }

    foreach ($releases as $release) {
        run("rm -rf releases/$release");
    }

})->desc('Cleaning up old releases');


/**
 * Helper task
 */
task('deploy:start', function () {
});


/**
 * Helper task
 */
task('deploy:end', function () {
});


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


 
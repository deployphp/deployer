<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Deployer\Server;

function releases()
{
    $releases = run("ls releases");
    $releases = explode("\n", $releases);
    rsort($releases);

    return array_filter($releases, function ($release) {
        $release = trim($release);
        return !empty($release);
    });
}

task('rollback', function () {
    info('Rollback to previous release');

    $basePath = Server\Current::getServer()->getConfiguration()->getPath();

    $releases = releases();

    if (count($releases) >= 2) {
        $releaseDir = "releases/" . $releases[1];
        run("rm -f current");
        run("ln -s $basePath/$releaseDir current");
    } else {
        writeln("<comment>No more releases you can revert to.</comment>");
    }

    ok();
})->description('Rollback to previous release');

task('deploy:prepare', function () {
    info('Preparing server for deploy');

    $basePath = Server\Current::getServer()->getConfiguration()->getPath();

    // Check if base dir exist.
    run("if [ ! -d \"$basePath\" ]; then mkdir $basePath; fi", true);

    // Create releases dir.
    run("if [ ! -d \"releases\" ]; then mkdir releases; fi");

    // Create shared dir.
    run("if [ ! -d \"shared\" ]; then mkdir shared; fi");

    ok();
})->description('Prepare server for deploy');

task('deploy:update_code', function () {
    info('Updating code');

    $repository = get('repository', null);

    if (null === $repository) {
        throw new \RuntimeException('You have to specify repository.');
    }

    $release = date('Ymd') . substr((string)time(), -5);
    $releaseDir = "releases/$release";

    set('release', $release);
    set('release_dir', $releaseDir);

    run("git clone -q $repository $releaseDir");
    run("chmod -R g+w $releaseDir");
    run("touch $releaseDir/$release.release");

    ok();
})->description('Update code');

task('deploy:create_cache_dir', function () {
    info('Creating cache dir');

    $releaseDir = get('release_dir', null);

    if (null !== $releaseDir) {
        $cacheDir = "$releaseDir/app/cache";

        set('cache_dir', $cacheDir);

        run("if [ -d \"$cacheDir\" ]; then rm -rf $cacheDir; fi");

        run("mkdir -p $cacheDir");
        run("chmod -R 0777 $cacheDir");
        run("chmod -R g+w $cacheDir");
    }

    ok();
})->description('Create cache dir');

after('deploy:update_code', 'deploy:create_cache_dir');

task('deploy:shared', function () {
    info('Creating symlinks for shared files');

    $basePath = Server\Current::getServer()->getConfiguration()->getPath();
    $sharedDirBase = "$basePath/shared";

    $releaseDir = get('release_dir', null);
    if (null !== $releaseDir) {
        // Shared dirs
        $sharedDirs = (array)get('shared_dirs', ['app/logs']);
        foreach ($sharedDirs as $dir) {
            // Remove dir from source
            run("if [ -d \"$releaseDir/$dir\" ]; then rm -rf $releaseDir/$dir; fi");

            // Create shared dir
            run("mkdir -p $sharedDirBase/$dir");

            // Symlink
            run("ln -nfs $sharedDirBase/$dir $releaseDir/$dir");
        }

        // Shared files
        $sharedFiles = (array)get('shared_dirs', ['app/config/parameters.yml']);
        foreach ($sharedFiles as $file) {
            // Create dir of shared file
            run("mkdir -p $sharedDirBase/" . dirname($file));

            // Touch shared file
            run("touch $sharedDirBase/$file");

            // Symlink
            run("ln -nfs $sharedDirBase/$file $releaseDir/$file");
        }
    }

    ok();
})->description('Create symlinks for shared directories and files');

task('deploy:assets', function () {
    info('Normalizing asset timestamps');

    $basePath = Server\Current::getServer()->getConfiguration()->getPath();
    $releaseDir = get('release_dir', null);

    if (null !== $releaseDir) {
        $assets = get('assets', ['web/css', 'web/images', 'web/js']);

        $assets = array_map(function ($asset) use ($releaseDir, $basePath) {
            return "$basePath/$releaseDir/$asset";
        }, $assets);
        $assets = implode(' ', $assets);

        $time = date('Ymdhi.s');

        run("find $assets -exec touch -t $time {} ';' &> /dev/null || true");
    }

    ok();
})->description('Normalize asset timestamps');

task('deploy:vendors', function () {
    info('Installing vendors');

    $releaseDir = get('release_dir', null);

    if (null !== $releaseDir) {
        $isComposer = run("if [ -e $releaseDir/composer.phar ]; then echo 'true'; fi");

        if ('true' !== $isComposer) {
            run("cd $releaseDir && curl -s http://getcomposer.org/installer | php");
        }

        $prod = get('env', 'prod');

        run("cd $releaseDir && SYMFONY_ENV=$prod php composer.phar install --no-dev --verbose --prefer-dist --optimize-autoloader --no-progress");
    }

    ok();
})->description('Install vendors');

task('deploy:assetic:dump', function () {
    info('Dumping assets');

    $prod = get('env', 'prod');
    run("php app/console assetic:dump --env=$prod --no-debug");

    ok();
})->description('Dump all assets to the filesystem');

task('deploy:cache:warmup', function () {
    info('Warming up cache');

    $cacheDir = get('cache_dir', null);

    if (null !== $cacheDir) {
        $prod = get('env', 'prod');
        run("php app/console cache:warmup  --env=$prod --no-debug");
        run("chmod -R g+w $cacheDir");
    }

    ok();
})->description('Warm up cache');

task('database:migrate', function () {
    info('Migrating database');

    $prod = get('env', 'prod');
    run("php app/console doctrine:migrations:migrate --env=$prod --no-debug --no-interaction");

    ok();
})->description('Migrate database');

task('deploy:clear_controllers', function () {
    run("rm -f web/app_*.php");
})->description('Remove app_dev.php files');

after('deploy:update_code', 'deploy:clear_controllers');

task('deploy:symlink', function () {
    $basePath = Server\Current::getServer()->getConfiguration()->getPath();
    $releaseDir = get('release_dir', null);

    if (null !== $releaseDir) {
        run("rm -f current");
        run("ln -s $basePath/$releaseDir current");
    }
})->description('Create symlink to last release');

task('cleanup', function () {
    info('Cleaning up old releases');

    $releases = releases();

    $keep = get('keep_releases', 3);

    while ($keep > 0) {
        array_shift($releases);
        --$keep;
    }

    foreach ($releases as $release) {
        run("rm -rf releases/$release");
    }

    ok();
})->description('Cleanup old releases');

task('deploy', [
    'deploy:prepare',
    'deploy:update_code',
    'deploy:shared',
    'deploy:assets',
    'deploy:vendors',
    'deploy:assetic:dump',
    (get('automigrate', false) ? 'migrate' : function () {
    }),
    'deploy:cache:warmup',
    'deploy:symlink',
    'cleanup'
])->description('Deploy your project');

after('deploy', function () {
    $host = Server\Current::getServer()->getConfiguration()->getHost();
    writeln("<info>Successfully deployed on</info> <fg=cyan>$host</fg=cyan>");
});


 
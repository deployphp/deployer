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
    $releases = env()->getReleases();
    $currentReleasePath = env()->getReleasePath();

    if (isset($releases[1])) {
        $releaseDir = "{$basePath}/releases/{$releases[1]}";
        // Symlink to old release.
        run("rm -f current");
        run("ln -s $releaseDir current");

        // Remove release
        run("rm -rf $currentReleasePath");
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
    run("if [ ! -d $(echo $basePath) ]; then mkdir $basePath; fi", true);

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

    env()->setReleasePath($releasePath);
    env()->set('is_new_release', true);

    run("git clone --recursive -q $repository $releasePath");
    run("chmod -R g+w $releasePath");
})->desc('Updating code');


/**
 * Delete new release if something goes wrong
 */
task('deploy:rollback', function () {
    if (env()->get('is_new_release', false)) {
        $server = config()->getName();
        writeln("<error>Rolling back to previous release on server $server</error>");

        $releasePath = env()->getReleasePath();
        // Remove release
        run("rm -rf $releasePath");
    } else {
        writeln("<comment>If you want to rollback run \"rollback\" task</comment>");
    }
});


/**
 * Create symlinks for shared directories and files
 */
task('deploy:shared', function () {
    $basePath = config()->getPath();
    $sharedPath = "$basePath/shared";
    $releasePath = env()->getReleasePath();

    // User specified shared directories
    $sharedDirs = (array)get('shared_dirs', []);

    foreach ($sharedDirs as $dir) {
        // Remove dir from source
        run("if [ -d $(echo $releasePath/$dir) ]; then rm -rf $releasePath/$dir; fi");

        // Create shared dir if does not exist
        run("mkdir -p $sharedPath/$dir");

        // Symlink shared dir to release dir
        run("ln -nfs $sharedPath/$dir $releasePath/$dir");
    }

    // User specified shared files
    $sharedFiles = (array)get('shared_files', []);

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
    $dirs = (array)get('writeable_dirs', []);

    foreach ($dirs as $dir) {
        run("chmod -R 0777 $dir");
        run("chmod -R g+w $dir");
    }
})->desc('Make writeable dirs');


/**
 * Vendors
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
    $releases = env()->getReleasesByTime();

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
 * Helper tasks
 */
task('deploy:start', function () {
});
task('deploy:end', function () {
});

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

    if (get('branch', false)) {
        $branch = get('branch', false);
        run("git clone --recursive -q $repository --branch $branch $releasePath");
    } else {
        run("git clone --recursive -q $repository $releasePath");
    }

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
 * Make writable dirs
 */
task('deploy:writable_dirs', function () {
    $user = config()->getUser();
    $wwwUser = config()->getWwwUser();
    $permissionMethod = get('permission_method', 'acl');
    $releasePath = env()->getReleasePath();

    cd($releasePath);

    // User specified writable dirs
    $dirs = (array)get('writable_dirs', []);

    switch ($permissionMethod) {
        case 'acl':
            $run = run("if which setfacl; then echo \"ok\"; fi");
            if (empty($run)) {
                writeln('<comment>Enable ACL support and install "setfacl"</comment>');
                return;
            }

            $commands = [
                'setfacl -R -m u:' . $user . ':rwX -m u:' . $wwwUser . ':rwX %s',
                'setfacl -dR -m u:' . $user . ':rwx -m u:' . $wwwUser . ':rwx %s'
            ];
            break;
        case 'chmod':
            $commands = [
                'chmod +a "' . $user . ' allow delete,write,append,file_inherit,directory_inherit" %s',
                'chmod +a "' . $wwwUser . ' allow delete,write,append,file_inherit,directory_inherit" %s'
            ];
            break;
        case 'chmod_bad':
            $commands = ['chmod -R a+w %s'];
            break;
    }

    foreach ($dirs as $dir) {
        foreach ($commands as $command) {
            run(sprintf($command, $dir));
        }
    }
})->desc('Make writable dirs');


/**
 * Vendors
 */
task('deploy:vendors', function () {
    $releasePath = env()->getReleasePath();

    cd($releasePath);
    $prod = get('env', 'prod');
    $php = php();
    $isComposer = run("if [ -e $releasePath/composer.phar ]; then echo 'true'; fi");

    if ('true' !== $isComposer) {
        run("curl -s http://getcomposer.org/installer | $php");
    }

    // Check if we're to copy the vendors
    if (get('composer_copy_vendors', false)) {
        $releases = env()->getReleases();

        if (isset($releases[1])) {
            // Existing previous release, so copy vendors folder from it
            $basePath = config()->getPath();
            $vendorsDir = "{$basePath}/releases/{$releases[1]}/vendor";

            run("if [ -d $(echo $vendorsDir) ]; then cp -r $vendorsDir $releasePath; fi");
        }
    }

    $options = get('composer_install_options', '--no-dev --verbose --prefer-dist --optimize-autoloader --no-progress');
    run("SYMFONY_ENV=$prod $php composer.phar install $options");

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

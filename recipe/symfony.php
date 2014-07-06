<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Deployer\Server;

task('rollback', function () {
    writeln('<error>Rollback to previous release</error>');
})->description('Rollback to previous release');

task('prepare', function () {
    $basePath = Server\Current::getServer()->getConfiguration()->getPath();

    // Check if base dir exist.
    run("if [ ! -d \"$basePath\" ]; then mkdir $basePath; fi", true);

    // Create releases dir.
    run("if [ ! -d \"releases\" ]; then mkdir releases; fi");

    // Create shared dir.
    run("if [ ! -d \"shared\" ]; then mkdir shared; fi");

})->description('Preparing server for deploy');

task('deploy:update_code', function () {
    $repository = get('repository', null);

    if (null === $repository) {
        throw new \RuntimeException('You have to specify repository.');
    }

    $release = date('Ymdhis');
    $releaseDir = "releases/$release";
    set('releaseDir', $releaseDir);

    run("git clone -q $repository $releaseDir");
    run("chmod -R g+w $releaseDir");
})->description('Updating code');

task('deploy:create_cache_dir', function () {
    $releaseDir = get('releaseDir', null);

    if (null !== $releaseDir) {
        $cacheDir = "$releaseDir/app/cache";

        run("if [ -d \"$cacheDir\" ]; then rm -rf $cacheDir; fi");

        run("mkdir -p $cacheDir");
        run("chmod -R 0777 $cacheDir");
        run("chmod -R g+w $cacheDir");
    }
})->description('Create cache dir');

after('deploy:update_code', 'deploy:create_cache_dir');

task('deploy:shared', function () {
    $basePath = Server\Current::getServer()->getConfiguration()->getPath();
    $sharedDirBase = "$basePath/shared";

    $releaseDir = get('releaseDir', null);
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
})->description('Creating symlinks for shared directories and files');

task('deploy', [
    'prepare',
    'deploy:update_code',
    'deploy:shared'
]);

after('deploy', function () {
    $host = Server\Current::getServer()->getConfiguration()->getHost();
    writeln("<info>Successfully deployed on $host</info>");
});
 
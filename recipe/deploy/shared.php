<?php
namespace Deployer;

use Deployer\Exception\Exception;
use Symfony\Component\Console\Output\OutputInterface;

// List of dirs what will be shared between releases.
// Each release will have symlink to those dirs stored in {{deploy_path}}/shared dir.
// ```php
// set('shared_dirs', ['storage']);
// ```
set('shared_dirs', []);

// List of files what will be shared between releases.
// Each release will have symlink to those files stored in {{deploy_path}}/shared dir.
// ```php
// set('shared_files', ['.env']);
// ```
set('shared_files', []);

desc('Creates symlinks for shared files and dirs');
task('deploy:shared', function () {
    $sharedPath = "{{deploy_path}}/shared";

    // Validate shared_dir, find duplicates
    foreach (get('shared_dirs') as $a) {
        foreach (get('shared_dirs') as $b) {
            if ($a !== $b && strpos(rtrim($a, '/') . '/', rtrim($b, '/') . '/') === 0) {
                throw new Exception("Can not share same dirs `$a` and `$b`.");
            }
        }
    }

    $copyVerbosity = output()->getVerbosity() === OutputInterface::VERBOSITY_DEBUG ? 'v' : '';

    foreach (get('shared_dirs') as $dir) {
        // Make sure all path without tailing slash.
        $dir = trim($dir, '/');

        // Check if shared dir does not exist.
        if (!test("[ -d $sharedPath/$dir ]")) {
            // Create shared dir if it does not exist.
            run("mkdir -p $sharedPath/$dir");
            // If release contains shared dir, copy that dir from release to shared.
            if (test("[ -d $(echo {{release_path}}/$dir) ]")) {
                run("cp -r$copyVerbosity {{release_path}}/$dir $sharedPath/" . dirname($dir));
            }
        }

        // Remove from source.
        run("rm -rf {{release_path}}/$dir");

        // Create path to shared dir in release dir if it does not exist.
        // Symlink will not create the path and will fail otherwise.
        run("mkdir -p `dirname {{release_path}}/$dir`");

        // Symlink shared dir to release dir
        run("{{bin/symlink}} $sharedPath/$dir {{release_path}}/$dir");
    }

    foreach (get('shared_files') as $file) {
        $dirname = dirname(parse($file));

        // Create dir of shared file if not existing
        if (!test("[ -d $sharedPath/$dirname ]")) {
            run("mkdir -p $sharedPath/$dirname");
        }

        // Check if shared file does not exist in shared.
        // and file exist in release
        if (!test("[ -f $sharedPath/$file ]") && test("[ -f {{release_path}}/$file ]")) {
            // Copy file in shared dir if not present
            run("cp -r$copyVerbosity {{release_path}}/$file $sharedPath/$file");
        }

        // Remove from source.
        run("if [ -f $(echo {{release_path}}/$file) ]; then rm -rf {{release_path}}/$file; fi");

        // Ensure dir is available in release
        run("if [ ! -d $(echo {{release_path}}/$dirname) ]; then mkdir -p {{release_path}}/$dirname;fi");

        // Touch shared
        run("[ -f $sharedPath/$file ] || touch $sharedPath/$file");

        // Symlink shared dir to release dir
        run("{{bin/symlink}} $sharedPath/$file {{release_path}}/$file");
    }
});

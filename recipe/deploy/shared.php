<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Creating symlinks for shared files and dirs');
task('deploy:shared', function () {
    foreach (get('shared_dirs') as $dir) {
        // Check if shared dir does not exists.
        if (!test("[ -d {{shared_path}}/$dir ]")) {
            // Create shared dir if it does not exist.
            run("mkdir -p {{shared_path}}/$dir");

            // If release contains shared dir, copy that dir from release to shared.
            if (test("[ -d $(echo {{release_path}}/$dir) ]")) {
                run("cp -rv {{release_path}}/$dir {{shared_path}}/" . dirname($dir));
            }
        }

        // Remove from source.
        run("rm -rf {{release_path}}/$dir");

        // Create path to shared dir in release dir if it does not exist.
        // Symlink will not create the path and will fail otherwise.
        run("mkdir -p `dirname {{release_path}}/$dir`");

        // Symlink shared dir to release dir
        run("{{bin/symlink}} {{shared_path}}/$dir {{release_path}}/$dir");
    }

    foreach (get('shared_files') as $file) {
        $dirname = dirname($file);
        // Remove from source.
        run("if [ -f $(echo {{release_path}}/$file) ]; then rm -rf {{release_path}}/$file; fi");

        // Ensure dir is available in release
        run("if [ ! -d $(echo {{release_path}}/$dirname) ]; then mkdir -p {{release_path}}/$dirname;fi");

        // Create dir of shared file
        run("mkdir -p {{shared_path}}/" . $dirname);

        // Touch shared
        run("touch {{shared_path}}/$file");

        // Symlink shared dir to release dir
        run("{{bin/symlink}} {{shared_path}}/$file {{release_path}}/$file");
    }
});

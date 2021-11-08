<?php
namespace Deployer;

// List of dirs to copy between releases.
// For example you can copy `node_modules` to speedup npm install.
set('copy_dirs', []);

desc('Copies directories');
task('deploy:copy_dirs', function () {
    if (has('previous_release')) {
        foreach (get('copy_dirs') as $dir) {
            // Make sure all path without tailing slash.
            $dir = trim($dir, '/');

            if (test("[ -d {{previous_release}}/$dir ]")) {
                $destinationDir = '';
                if (strpos($dir, '/') !== false) {
                    $destinationDir = substr($dir, 0, strrpos($dir, '/') + 1);
                }
                run("mkdir -p {{release_path}}/$dir");
                // -a, --archive
                //        copy directories recursively, preserve all attributes,
                //        never follow symbolic links in SOURCE
                // -f, --force
                //        if  an existing destination file cannot be opened, remove it and try again (this option is ignored when the -n
                //        option is also used)
                run("cp -af {{previous_release}}/$dir {{release_path}}/$destinationDir");
            }
        }
    }
});

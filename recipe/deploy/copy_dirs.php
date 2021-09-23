<?php
namespace Deployer;

desc('Copy directories');
task('deploy:copy_dirs', function () {
    if (has('previous_release')) {
        foreach (get('copy_dirs') as $dir) {
            if (substr($dir, -1) === '/') {
                throw new \RuntimeException('Entries in config parameter "copy_dirs" must not end with "/"');
            }
            if (test("[ -d {{previous_release}}/$dir ]")) {
                $destinationDir = '';
                if (strpos($dir, '/') !== false) {
                    $destinationDir = substr($dir, 0, strrpos($dir, '/') + 1);
                }
                run("mkdir -p {{release_path}}/$dir");
                run("cp -rpf {{previous_release}}/$dir {{release_path}}/$destinationDir");
            }
        }
    }
});

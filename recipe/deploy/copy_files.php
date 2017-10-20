<?php
/* (c) Fabian Kowalczyk <fabkow@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Copy files');

/**
 * Copy files from previous to current release
 *
 * How to use it:
 *
 * set('copy_files', [
 *  'dist/file1.txt',
 *  'dist/file2.txt',
 *  'dist/css/main.css'
 * ]);
 *
 */
task('deploy:copy_files', function () {
    if (has('previous_release')) {
        foreach (get('copy_files') as $file) {
            if (test("[ -f {{previous_release}}/$file ]")) {
                run("rsync -avR {{previous_release}}/./$file {{release_path}}");
            }
        }
    }
});

<?php
/* (c) Joren Van Hee
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';

set('shared_dirs', [
    'storage',
]);

set('shared_files', [
    '.env',
]);

// https://docs.craftcms.com/v3/installation.html#step-2-set-the-file-permissions
set('writable_dirs', [
    'storage',
    'vendor',
    'web/cpresources',
]);

task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');

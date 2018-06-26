<?php
/* (c) Leopold Jacquot <leopold.jacquot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';

set('shared_files', ['config/settings.inc.php', '.htaccess']);
set('shared_dirs', [
         'img',
         'log',
         'download',
         'upload',
         'translations',
         'mails',
         'themes/default-bootstrap/lang',
         'themes/default-bootstrap/mails',
         'themes/default-bootstrap/pdf/lang',
    ]
);
set('writable_dirs', [
       'img',
       'log',
       'cache',
       'download',
       'upload',
       'translations',
       'mails',
       'themes/default-bootstrap/lang',
       'themes/default-bootstrap/mails',
       'themes/default-bootstrap/pdf/lang',
       'themes/default-bootstrap/cache',
   ]
);

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
    ]
)->desc('Deploy your project');

after('deploy', 'success');

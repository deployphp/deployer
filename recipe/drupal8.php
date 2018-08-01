<?php
/* (c) Sergio Carracedo <info@sergiocarraedo.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';

task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup'
]);

//Set drupal site. Change if you use different site
set('drupal_site', 'default');


//Drupal 8 shared dirs
set('shared_dirs', [
    'sites/{{drupal_site}}/files',
]);

//Drupal 8 shared files
set('shared_files', [
    'sites/{{drupal_site}}/settings.php',
    'sites/{{drupal_site}}/services.yml',
]);

//Drupal 8 Writable dirs
set('writable_dirs', [
    'sites/{{drupal_site}}/files',
]);

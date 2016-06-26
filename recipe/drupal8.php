<?php
/* (c) Sergio Carracedo <info@sergiocarraedo.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/common.php';

task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:symlink',
    'cleanup'
]);

after('deploy:prepare', 'deploy:acquire_lock');
after('deploy:symlink', 'deploy:release_lock');

//Set drupal site. Change if you use different site
env('drupal_site', 'default');


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

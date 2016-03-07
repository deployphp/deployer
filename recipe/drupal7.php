<?php
/* (c) Sergio Carracedo <info@sergiocarraedo.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';

taskGroup('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:symlink',
    'cleanup'
]);

//Set drupal site. Change if you use different site
env('drupal_site', 'default');


//Drupal 7 shared dirs
set('shared_dirs', [
    'sites/{{drupal_site}}/files',
]);

//Drupal 7 sharef files
set('shared_files', [
    'sites/{{drupal_site}}/settings.php',
]);

//Drupal 7 Writable dirs
set('writable_dirs', [
    'sites/{{drupal_site}}/files',
]);


//Create and upload Drupal 7 settings.php using values from secrets
task('drupal:settings', function () {
    if (askConfirmation('Are you sure to generate and upload settings.php file?')) {
        $basepath = dirname(__FILE__) . '/drupal7';

        //Import secrets
        $secrets = env('settings');

        //Prepare replacement variables
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($secrets)
        );

        $replacements = [];
        foreach ($iterator as $key => $value) {
            $keys = [];
            for ($i = $iterator->getDepth(); $i > 0; $i --) {
                $keys[] = $iterator->getSubIterator($i - 1)->key();
            }
            $keys[] = $key;

            $replacements['{{' . implode('.', $keys) . '}}'] = $value;
        }

        //Create settings from template
        $settings = file_get_contents($basepath . '/settings.php');

        $settings = strtr($settings, $replacements);

        writeln('settings.php created succesfuly');

        $tmpFilename = tempnam($basepath, 'tmp_settings_');
        file_put_contents($tmpFilename, $settings);

        upload($tmpFilename, '{{deploy_path}}/shared/sites/{{drupal_site}}/settings.php');

        unlink($tmpFilename);
    }

});

//Upload Drupal 7 files folder
task('drupal:upload_files', function () {
    if (askConfirmation('Are you sure?')) {
        upload('sites/{{drupal_site}}/files', '{{deploy_path}}/shared/sites/{{drupal_site}}/files');
    }
});

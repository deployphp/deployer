<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['drupal7']);

task('deploy', [
    'deploy:prepare',
    'deploy:publish',
]);

//Set Drupal 7 site. Change if you use different site
set('drupal_site', 'default');

//Drupal 7 shared dirs
set('shared_dirs', [
    'sites/{{drupal_site}}/files',
]);

//Drupal 7 shared files
set('shared_files', [
    'sites/{{drupal_site}}/settings.php',
]);

//Drupal 7 writable dirs
set('writable_dirs', [
    'sites/{{drupal_site}}/files',
]);


//Create and upload Drupal 7 settings.php using values from secrets
task('drupal:settings', function () {
    if (askConfirmation('Are you sure to generate and upload settings.php file?')) {

        //Get template
        $template = get('settings_template');

        //Import secrets
        $secrets = get('settings');

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
        $settings = file_get_contents($template);

        $settings = strtr($settings, $replacements);

        writeln('settings.php created successfully');

        $tmpFilename = tempnam(sys_get_temp_dir(), 'tmp_settings_');
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

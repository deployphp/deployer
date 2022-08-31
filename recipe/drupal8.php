<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['drupal8']);

task('deploy', [
    'deploy:prepare',
    'deploy:publish',
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

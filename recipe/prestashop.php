<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['prestashop']);

set('shared_files', [
    'config/settings.inc.php',
    '.htaccess',
]);
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
]);
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
]);

desc('Deploys your project');
task('deploy', [
        'deploy:prepare',
        'deploy:vendors',
        'deploy:publish',
    ]
);

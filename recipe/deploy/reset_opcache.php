<?php

namespace Deployer;

set('reset_opcache_nonce', bin2hex(random_bytes(8)));
set('reset_opcache_document_root', '{{release_or_current_path}}');
set('reset_opcache_app_url', 'https://{{hostname}}');

desc('Place PHP OPcache Reset Script');
task('deploy:reset_opcache:prepare', function () {
    $phpScript = <<<PHP
    <?php
    if (function_exists("clearstatcache")) {
        clearstatcache(true); // Clear realpath cache
    }
    if (function_exists("opcache_reset")) {
        opcache_reset(); // Clear opcache
    }
    @unlink(__FILE__);
    echo "success";
    PHP;

    run('echo "$PHP_OPCACHE_RESET_SCRIPT" > {{reset_opcache_document_root}}/reset_opcache-{{reset_opcache_nonce}}.php', env: ['PHP_OPCACHE_RESET_SCRIPT' => $phpScript]);
});

desc('Execute PHP OPcache Reset');
task('deploy:reset_opcache:execute', function () {
    $result = fetch("{{reset_opcache_app_url}}/reset_opcache-{{reset_opcache_nonce}}.php");
    if ($result !== 'success') {
        throw error('Reset PHP OPcache failed!');
    }
});

desc('Reset PHP OPcache');
task('deploy:reset_opcache', [
    'deploy:reset_opcache:prepare',
    'deploy:reset_opcache:execute',
]);

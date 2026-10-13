<?php

namespace Deployer;

// Another spec may already have loaded common.php in this process, and the require_once in
// magento2.php would then skip it, leaving the tasks it hooks on unregistered.
require 'recipe/common.php';
require 'recipe/magento2.php';

$cases = __TEMP_DIR__ . '/magento-theme';

foreach (['removed', 'reparented', 'unchanged', 'recorded'] as $case) {
    localhost($case)
        ->set('release_or_current_path', "$cases/$case/release")
        ->set('previous_release', "$cases/$case/previous")
        ->set('theme_fingerprint_file', "$cases/$case/theme_fingerprint")
        ->set('database_upgrade_needed', false)
        ->set('full_upgrade_needed', false);
}

task('test_theme_registration_needed', function () {
    writeln(get('theme_registration_needed') ? 'REGISTRATION NEEDED' : 'REGISTRATION NOT NEEDED');
});

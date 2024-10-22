<?php

namespace Deployer;

// Defines "what" text for the 'deploy:info' task.
// Uses one of the following sources:
// 1. Repository name
set('what', function () {
    $repo = get('repository');
    if (!empty($repo)) {
        return preg_replace('/\.git$/', '', basename($repo));
    }
    return 'something';
});

// Defines "where" text for the 'deploy:info' task.
// Uses one of the following sources:
// 1. Host's stage label
// 2. Host's alias
set('where', function () {
    $labels = get('labels');
    if (isset($labels['stage'])) {
        return $labels['stage'];
    }
    return currentHost()->getAlias();
});

desc('Displays info about deployment');
task('deploy:info', function () {
    $releaseName = test('[ -d {{deploy_path}}/.dep ]') ? get('release_name') : 1;

    info("deploying <fg=green;options=bold>{{what}}</> to <fg=magenta;options=bold>{{where}}</> (release <fg=magenta;options=bold>{$releaseName}</>)");
});

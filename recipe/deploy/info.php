<?php
namespace Deployer;

desc('Displays info about deployment');
task('deploy:info', function () {
    $releaseName = test('[ -d {{deploy_path}}/.dep ]') ? get('release_name') : 1;
    
    info("deploying <fg=magenta;options=bold>{{target}}</> (release <fg=magenta;options=bold>{$releaseName}</>)");
});

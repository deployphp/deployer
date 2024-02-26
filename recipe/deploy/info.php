<?php
namespace Deployer;

desc('Displays info about deployment');
task('deploy:info', function () {
    info("deploying <fg=magenta;options=bold>{{target}}</> (release <fg=magenta;options=bold>{{release_name}}</>)");
});

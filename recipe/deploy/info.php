<?php
namespace Deployer;

task('deploy:info', function () {
    $what = '';
    $branch = get('branch');
    if (!empty($branch)) {
        $what = "<fg=magenta;options=bold>$branch</>";
    }
    if (input()->hasOption('tag') && !empty(input()->getOption('tag'))) {
        $tag = input()->getOption('tag');
        $what = "tag <fg=magenta;options=bold>$tag</>";
    }
    if (input()->hasOption('revision') && !empty(input()->getOption('revision'))) {
        $revision = input()->getOption('revision');
        $what = "revision <fg=magenta;options=bold>$revision</>";
    }
    if (empty($what)) {
        $what = "<fg=magenta;options=bold>HEAD</>";
    }
    info("deploying $what");
})
    ->shallow();

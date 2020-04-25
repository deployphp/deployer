<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    info("deploy $what on " . currentHost()->getTag());
})
    ->shallow();

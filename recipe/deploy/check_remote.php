<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;

// Check and save the remote HEAD/revision and compare it with the existing saved one (if any).
// This avoid unnecessary releases when the last commit id matches the existing one (HEAD).
desc('Check remote head');
task('deploy:check_remote', function () {
    $repository = get('repository');
    if (empty($repository)) {
        return;
    }

    $revision = input()->getOption('revision') ?? null;
    $remoteHead = $revision ?? run(sprintf('%s ls-remote %s HEAD | tr -d "HEAD"', get('bin/git'), $repository));

    if (null == input()->getOption('tag')) {
        $headPath = get('deploy_path') . '/.dep/HEAD';
        $headContents = run(sprintf('test -e %s && cat %1$s', $headPath));
        // Check if HEAD file is exists and then compare it.
        if (trim($headContents) === trim($remoteHead)) {
            throw new GracefulShutdownException("Already up-to-date.");
        }
    }

    run("cd {{deploy_path}} && echo $remoteHead > .dep/HEAD");
});

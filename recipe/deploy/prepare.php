<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;
use function Deployer\Support\str_contains;

desc('Preparing host for deploy');
task('deploy:prepare', function () {
    // Check if shell is POSIX-compliant
    $result = run('echo $0');

    if (!str_contains($result, 'bash') && !str_contains($result, 'sh')) {
        throw new \RuntimeException(
            'Shell on your server is not POSIX-compliant. Please change to sh, bash or similar.'
        );
    }

    run('if [ ! -d {{deploy_path}} ]; then mkdir -p {{deploy_path}}; fi');

    // Check for existing /current directory (not symlink)
    $result = test('[ ! -L {{deploy_path}}/current ] && [ -d {{deploy_path}}/current ]');
    if ($result) {
        throw new \RuntimeException('There already is a directory (not symlink) named "current" in ' . get('deploy_path') . '. Remove this directory so it can be replaced with a symlink for atomic deployments.');
    }

    // Create metadata .dep dir.
    run("cd {{deploy_path}} && if [ ! -d .dep ]; then mkdir .dep; fi");

    // Create releases dir.
    run("cd {{deploy_path}} && if [ ! -d releases ]; then mkdir releases; fi");

    // Create shared dir.
    run("cd {{deploy_path}} && if [ ! -d shared ]; then mkdir shared; fi");

    // Check and save the remote HEAD/revision and compare it with the existing saved one (if any)
    // This avoid unnecessary releases when the last commit id matches the existing one (HEAD)
    $repository  = trim(get('repository'));
    $revision    = input()->getOption('revision') ?? null;
    $remoteHead  = $revision ?? run(sprintf('%s ls-remote %s HEAD | tr -d "HEAD"', get('bin/git'), $repository));

    if (true === get('check_remote_head') && null == input()->getOption('tag')) {
        $headPath = trim(get('deploy_path').'/.dep/HEAD');
        $headContents = run(sprintf('test -e %s && cat %1$s', $headPath));
        //check if HEAD file is exists and then compare it
        if (trim($headContents) === trim($remoteHead)) {
            throw new GracefulShutdownException("Already up-to-date.");
        }
    }
    run("cd {{deploy_path}} && echo ".$remoteHead.' > .dep/HEAD');
});

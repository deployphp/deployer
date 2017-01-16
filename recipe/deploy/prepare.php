<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Preparing server for deploy');
task('deploy:prepare', function () {
    // Check if shell is POSIX-compliant
    try {
        cd(''); // To run command as raw.
        $result = run('echo $0')->toString();
        if ($result == 'stdin: is not a tty') {
            throw new \RuntimeException(
                "Looks like ssh inside another ssh.\n" .
                "Help: http://goo.gl/gsdLt9"
            );
        }
    } catch (\RuntimeException $e) {
        $formatter = Deployer::get()->getHelper('formatter');

        $errorMessage = [
            "Shell on your server is not POSIX-compliant. Please change to sh, bash or similar.",
            "Usually, you can change your shell to bash by running: chsh -s /bin/bash",
        ];
        write($formatter->formatBlock($errorMessage, 'error', true));

        throw $e;
    }

    run('if [ ! -d {{deploy_path}} ]; then mkdir -p {{deploy_path}}; fi');

    // Check for existing /current directory (not symlink)
    $result = run('if [ ! -L {{current_path}} ] && [ -d {{current_path}} ]; then echo true; fi')->toBool();
    if ($result) {
        throw new \RuntimeException('There already is a directory (not symlink) named "' . get('current_dir') . '" in ' . get('deploy_path') . '. Remove this directory so it can be replaced with a symlink for atomic deployments.');
    }

    // Create metadata .dep dir.
    run("if [ ! -d {{dep_path}} ]; then mkdir {{dep_path}}; fi");

    // Create releases dir.
    run("if [ ! -d {{releases_path}} ]; then mkdir {{releases_path}}; fi");

    // Create shared dir.
    run("if [ ! -d {{shared_path}} ]; then mkdir {{shared_path}}; fi");
});

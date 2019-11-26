<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;

desc('Lock deploy');

task('deploy:lock', function () {
    $command = <<<'BASH'
declare -r LOCK_FILE='{{deploy_path}}/.dep/deploy.lock'

if [ -f ${LOCK_FILE} ]; then
    cat ${LOCK_FILE}
else
    echo '%s' > ${LOCK_FILE} && echo -n 'false'
fi
BASH;

    /** @var string $isLocked Return json encoded $context if locked, "false" otherwise. */
    $isLocked = run(
        \sprintf(
            $command,
            \json_encode(
                [
                    'created_by' => get('user'),
                    'created_at' => (new \DateTime())->format('c'),
                ],
                \JSON_PRETTY_PRINT
            )
        )
    );

    if ('false' === $isLocked) {
        return;
    }

    $context = \json_decode($isLocked, true);

    if (\JSON_ERROR_NONE !== \json_last_error()) { // backward compatibility
        throw new GracefulShutdownException('Deploy locked.');
    }

    throw new GracefulShutdownException(
        \sprintf(
            'Deploy locked by "%s" at "%s".',
            $context['created_by'],
            $context['created_at']
        )
    );
});

desc('Unlock deploy');
task('deploy:unlock', function () {
    run("rm -f {{deploy_path}}/.dep/deploy.lock");//always success
});

<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Exception;

/**
 * Then this exception thrown, it will not trigger "fail" callback.
 *
 *     fail('deploy', 'deploy:failed');
 *
 *     task('deploy', function () {
 *         throw new GracefulShutdownException(...);
 *     });
 *
 * In example above task `deploy:failed` will not be called.
 */
class GracefulShutdownException extends Exception
{
    const EXIT_CODE = 42;
}

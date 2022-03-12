<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Exception;

class TimeoutException extends Exception
{
    public function __construct(
        string $command,
        ?float $timeout
    ) {
        $message = sprintf('The command "%s" exceeded the timeout of %s seconds.', $command, $timeout);
        parent::__construct($message, 1);
    }
}

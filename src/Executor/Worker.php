<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\RunException;
use Deployer\Host\Host;
use Deployer\Task\Context;
use Deployer\Task\Task;
use Throwable;

class Worker
{
    /**
     * @var Deployer
     */
    private $deployer;

    public function __construct(Deployer $deployer)
    {
        $this->deployer = $deployer;
    }

    public function execute(Task $task, Host $host): int
    {
        try {
            Exception::setTaskSourceLocation($task->getSourceLocation());

            $context = new Context($host);
            $task->run($context);

            if ($task->getName() !== 'connect') {
                $this->deployer->messenger->endOnHost($host);
            }
            return 0;
        } catch (Throwable $e) {
            $this->deployer->messenger->renderException($e, $host);
            if ($e instanceof GracefulShutdownException) {
                return GracefulShutdownException::EXIT_CODE;
            }
            if ($e instanceof RunException) {
                return $e->getExitCode();
            }
            return 255;
        }
    }
}

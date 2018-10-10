<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Configuration\Configuration;
use Deployer\Exception\Exception;
use Deployer\Host\Host;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Context
{
    /**
     * @var Host
     */
    private $host;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Context[]
     */
    private static $contexts = [];

    public function __construct(
        Host $host,
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->host = $host;
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @param Context $context
     *
     * @return void
     */
    public static function push(Context $context)
    {
        self::$contexts[] = $context;
    }

    public static function has(): bool
    {
        return !empty(self::$contexts);
    }

    /**
     * @throws Exception
     */
    public static function get(): self
    {
        if (!self::has()) {
            throw new Exception('Context was required, but there\'s nothing there.');
        }

        return self::$contexts[count(self::$contexts) - 1];
    }

    /**
     * @return null|Context
     */
    public static function pop()
    {
        return array_pop(self::$contexts);
    }

    /**
     * Throws a Exception when not called within a task-context and therefore no Context is available.
     *
     * This method provides a useful error to the end-user to make him/her aware
     * to use a function in the required task-context.
     *
     * @return void
     *
     * @throws Exception
     */
    public static function required(string $callerName)
    {
        if (!self::has()) {
            throw new Exception("'$callerName' can only be used within a task.");
        }
    }

    public function getConfig(): Configuration
    {
        return $this->host->getConfig();
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function getHost(): Host
    {
        return $this->host;
    }
}

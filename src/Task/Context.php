<?php
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

    /**
     * @param Host $host
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct($host, InputInterface $input = null, OutputInterface $output = null)
    {
        $this->host = $host;
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @param Context $context
     */
    public static function push(Context $context)
    {
        self::$contexts[] = $context;
    }

    /**
     * @return bool
     */
    public static function has()
    {
        return !empty(self::$contexts);
    }

    /**
     * @return Context|false
     * @throws Exception
     */
    public static function get()
    {
        if (empty(self::$contexts)) {
            throw new Exception('Context was required, but there\'s nothing there.');
        }
        return end(self::$contexts);
    }

    /**
     * @return Context
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
     * @param string $callerName
     * @throws Exception
     */
    public static function required($callerName)
    {
        if (!self::get()) {
            throw new Exception("'$callerName' can only be used within a task.");
        }
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->host->getConfig();
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return Host
     */
    public function getHost()
    {
        return $this->host;
    }
}

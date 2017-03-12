<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Exception\ConfigurationException;
use Deployer\Exception\Exception;
use Deployer\Host\Configuration;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Server\Environment;
use Deployer\Server\ServerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Context
{
    /**
     * @var Host|Localhost
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
     * @param Host|Localhost $host
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct($host, InputInterface $input, OutputInterface $output)
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
     * @return Context|false
     */
    public static function get()
    {
        return end(self::$contexts);
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
            throw new ConfigurationException("'$callerName' can only be used within a task.");
        }
    }

    /**
     * @return Context
     */
    public static function pop()
    {
        return array_pop(self::$contexts);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->host->getConfiguration();
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
     * @return Host|Localhost
     */
    public function getHost()
    {
        return $this->host;
    }
}

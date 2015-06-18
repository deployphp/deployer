<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Server\Environment;
use Deployer\Server\ServerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Context
{
    /**
     * @var \Deployer\Server\ServerInterface|null
     */
    private $server;

    /**
     * @var \Deployer\Server\Environment|null
     */
    private $env;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface|null
     */
    private $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface|null
     */
    private $output;

    /**
     * @var $this[]
     */
    private static $contexts = [];

    /**
     * @param \Deployer\Server\ServerInterface|null $server
     * @param \Deployer\Server\Environment|null $env
     * @param \Symfony\Component\Console\Input\InputInterface|null $input
     * @param \Symfony\Component\Console\Output\OutputInterface|null $output
     */
    public function __construct($server, $env, $input, $output)
    {
        $this->server = $server;
        $this->env = $env;
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @param \Deployer\Task\Context $context
     */
    public static function push(Context $context)
    {
        self::$contexts[] = $context;
    }

    /**
     * @return \Deployer\Task\Context|bool
     */
    public static function get()
    {
        return end(self::$contexts);
    }

    /**
     * @return \Deployer\Task\Context
     */
    public static function pop()
    {
        return array_pop(self::$contexts);
    }

    /**
     * @return \Deployer\Server\Environment|null
     */
    public function getEnvironment()
    {
        return $this->env;
    }

    /**
     * @return null|\Symfony\Component\Console\Input\InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return null|\Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return \Deployer\Server\ServerInterface|null
     */
    public function getServer()
    {
        return $this->server;
    }
}

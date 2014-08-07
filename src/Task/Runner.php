<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Server\ServerInterface;
use Deployer\Local\LocalInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Runner
{
    private static $server;

    private static $local;

    /**
     * @var callable
     */
    private $closure;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $desc;

    /**
     * @param callable $closure
     * @param string $desc
     */
    public function __construct(\Closure $closure, $name = null, $desc = null)
    {
        $this->closure = $closure;
        $this->name = $name;
        $this->desc = $desc;
    }

    /**
     * Run closure.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param LocalInterface $local
     * @param ServerInterface $server
     */
    public function run(InputInterface $input, OutputInterface $output, LocalInterface $local, ServerInterface $server = null)
    {
        self::$local = $local;

        if ( $server ) {
            $server->getEnvironment()->set('working_path', $server->getConfiguration()->getPath());
        }
        self::$server = $server;

        call_user_func($this->closure, $input, $output);
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $desc
     */
    public function setDesc($desc)
    {
        $this->desc = $desc;
    }

    /**
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * @return ServerInterface
     */
    public static function server()
    {
        return self::$server;
    }

    /**
     * @return LocalInterface
     */
    public static function local()
    {
        return self::$local;
    }
}
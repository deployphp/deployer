<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Symfony\Component\Console\Input\InputInterface;

class Runner
{
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
     */
    public function run(InputInterface $input = null)
    {
        call_user_func($this->closure, $input);
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
}
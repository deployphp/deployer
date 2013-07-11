<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Task extends Command
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param $name
     * @param $description
     * @param callable $callback
     */
    public function __construct($name, $description, \Closure $callback)
    {
        parent::__construct($name);
        $this->setDescription($description);
        $this->callback = $callback;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->call();
    }

    public function call()
    {
        call_user_func($this->callback);
    }
}
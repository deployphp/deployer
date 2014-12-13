<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;


use Deployer\Server\ServerInterface;
use Deployer\Task\Task;
use Pure\Server;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ParallelExecutor implements ExecutorInterface
{
    /**
     * {@inheritdoc}
     */
    public function run($tasks, $servers, $input, $output)
    {
        $pure = new Server(3333);

        $ref = new \ReflectionObject($pure);
        /** @var $property \ReflectionProperty */
        $property = $ref->getProperty('loop');
        $property->setAccessible(true);
        /** @var $loop \React\EventLoop\LoopInterface */
        $loop = $property->getValue($pure);


        // Start workers for each server.
        $loop->addTimer(0, function () use ($servers) {
            foreach ($servers as $serverName => $server) {
                $process = new Process("php bin/dep worker --master=127.0.0.1:3333 --server=$serverName &");
                $process->disableOutput();
                $process->run();
            }
        });


        // Wait for output
        $loop->addPeriodicTimer(0, function () use ($pure) {
            $o = $pure->getStores()['Pure\Storage\QueueStorage'];
            
            /** @var $s \Pure\Storage\QueueStorage */
            foreach ($o as $n => $s) {
                while (count($s) > 0) {
                    echo "[$n] " . $s->dequeue() . "\n";
                }
            }
        });

        $pure->run();
    }
} 
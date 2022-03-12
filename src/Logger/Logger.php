<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Logger;

use Deployer\Component\ProcessRunner\Printer;
use Deployer\Host\Host;
use Deployer\Logger\Handler\HandlerInterface;

class Logger
{
    /**
     * @var HandlerInterface
     */
    private $handler;

    public function __construct(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    public function log(string $message): void
    {
        $this->handler->log("$message\n");
    }

    public function callback(Host $host): \Closure
    {
        return function ($type, $buffer) use ($host) {
            $this->printBuffer($host, $type, $buffer);
        };
    }

    public function printBuffer(Host $host, string $type, string $buffer): void
    {
        foreach (explode("\n", rtrim($buffer)) as $line) {
            $this->writeln($host, $type, $line);
        }
    }

    public function writeln(Host $host, string $type, string $line): void
    {
        $line = Printer::filterOutput($line);

        // Omit empty lines
        if (empty($line)) {
            return;
        }

        $this->log("[{$host->getAlias()}] $line");
    }
}

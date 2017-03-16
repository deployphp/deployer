<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Utility\ProcessOutputPrinter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Localhost
{
    use ConfigurationAccessor;
    use ProcessOutputPrinter;

    public function __construct()
    {
        $this->configuration = new Configuration();
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return 'localhost';
    }

    public function exec(OutputInterface $output, string $command, array $config = [])
    {
        $hostname = $this->getHostname();
        $defaults = [
            'timeout' => 300,
            'tty' => false,
        ];
        $config = array_merge($defaults, $config);


        $process = new Process($command);
        $process
            ->setTimeout($config['timeout'])
            ->setTty($config['tty'])
            ->mustRun($this->callback($output, $hostname));

        return $process->getOutput();
    }
}

<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Helper;

use Deployer\Console\Application;
use Deployer\Deployer;
use Deployer\Log\LogWriter;
use Symfony\Component\Console\Tester\ApplicationTester;

trait DeployerHelper
{
    /**
     * @var Deployer
     */
    protected $deployer;

    /**
     * @var ApplicationTester
     */
    protected $tester;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var LogWriter
     */
    protected $logger;

    /**
     * Init deployer
     */
    protected function initialize()
    {
        // Create App tester.
        $console = new Application();
        $console->setAutoExit(false);
        $console->setCatchExceptions(false);
        $this->tester = new ApplicationTester($console);

        // Prepare Deployer
        $this->input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $this->output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $this->logger = $this->getMock('Deployer\Log\LogWriter', ["writeLog"], ["deploy.log"]);
        $this->deployer = new Deployer($console, $this->input, $this->output);
    }
}

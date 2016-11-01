<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Helper;

use Deployer\Console\Application;
use Deployer\Deployer;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
     * @var EventDispatcher
     */
    protected $eventDispatcher;

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
        $this->eventDispatcher = $this->getMock('\Symfony\Component\EventDispatcher\EventDispatcher');

        $this->deployer = new Deployer($console, $this->input, $this->output, $this->eventDispatcher);
    }
}

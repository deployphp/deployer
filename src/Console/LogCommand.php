<?php

namespace Deployer\Console;

use Deployer\Deployer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;

class LogCommand extends Command
{
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @param Deployer $deployer
     */
    public function __construct(Deployer $deployer)
    {
        parent::__construct('log');
        $this->setDescription('Display the task-tree for a given task');
        $this->deployer = $deployer;
        $this->tree = [];
    }

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->addArgument(
            'log',
            InputArgument::OPTIONAL,
            'Task to display the tree for'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, InputInterface $output)
    {
        $this->output = $output;

        $rootTaskName = $input->getArgument('log');
//
//        $this->buildTree($rootTaskName);
//        $this->outputTree($rootTaskName);
    }
}
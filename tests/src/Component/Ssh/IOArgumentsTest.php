<?php

namespace Deployer\Component\Ssh;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class IOArgumentsTest extends TestCase
{
    public function testCollect()
    {
        $definition = new InputDefinition([
            new InputOption('option', 'o', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Set configuration option'),
            new InputOption('limit', 'l', InputOption::VALUE_REQUIRED, 'How many tasks to run in parallel?'),
            new InputOption('no-hooks', null, InputOption::VALUE_NONE, 'Run tasks without after/before hooks'),
            new InputOption('plan', null, InputOption::VALUE_NONE, 'Show execution plan'),
            new InputOption('start-from', null, InputOption::VALUE_REQUIRED, 'Start execution from this task'),
            new InputOption('log', null, InputOption::VALUE_REQUIRED, 'Write log to a file'),
            new InputOption('profile', null, InputOption::VALUE_REQUIRED, 'Write profile to a file' ),
            new InputOption('ansi', null, InputOption::VALUE_OPTIONAL, 'Force ANSI output' ),
        ]);

        $args = IOArguments::collect(
            new ArgvInput(['deploy', '-o', 'env=prod', '--ansi', '-l1'], $definition),
            new ConsoleOutput(OutputInterface::VERBOSITY_DEBUG, false)
        );

        self::assertEquals(['--option','env=prod', '--limit', '1', '-vvv'], $args);
    }
}

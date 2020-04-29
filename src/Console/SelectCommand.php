<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Executor\Planner;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Question\ChoiceQuestion;

abstract class SelectCommand extends Command
{
    protected $deployer;

    public function __construct(string $name, Deployer $deployer)
    {
        $this->deployer = $deployer;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->addOption('selector', 's', Option::VALUE_OPTIONAL, 'Host selector');

        // Add global options defined with `option()` func.
        $this->getDefinition()->addOptions($this->deployer->inputDefinition->getOptions());

        $this->addOption(
            'option',
            'o',
            Option::VALUE_REQUIRED | Option::VALUE_IS_ARRAY,
            'Sets configuration option'
        );
    }

    protected function selectHosts(Input $input, Output $output)
    {
        $output->getFormatter()->setStyle('success', new OutputFormatterStyle('green'));
        if (!$output->isDecorated()) {
            define('NO_ANSI', 'true');
        }
        $selectExpression = $input->getOption('selector');
        $this->parseOptions($input->getOption('option'));

        if (empty($selectExpression)) {
            $hostsAliases = [];
            foreach ($this->deployer->hosts as $host) {
                $hostsAliases[] = $host->alias();
            }
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                '<question>Select hosts:</question> (comma separated)',
                $hostsAliases
            );
            $question->setMultiselect(true);
            $question->setErrorMessage('There is no "%s" host.');
            $answer = $helper->ask($input, $output, $question);
            $answer = array_unique($answer);
            $hosts = $this->deployer->hosts->select(function (Host $host) use ($answer) {
                return in_array($host->alias(), $answer, true);
            });
        } else {
            $hosts = $this->deployer->selector->selectHosts($selectExpression);
        }

        if (empty($hosts)) {
            throw new Exception('No host selected');
        }

        return $hosts;
    }

    private function parseOptions(array $options)
    {
        foreach ($options as $option) {
            list($name, $value) = explode('=', $option);
            $value = $this->castValueToPhpType($value);
            $this->deployer->config->set($name, $value);
        }
    }

    private function castValueToPhpType($value)
    {
        switch ($value) {
            case 'true':
                return true;
            case 'false':
                return false;
            default:
                return $value;
        }
    }
}

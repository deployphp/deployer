<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Question\ChoiceQuestion;
use function Deployer\localhost;

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
        $this->addArgument('selector', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Host selector');
    }

    protected function selectHosts(Input $input, Output $output): array
    {
        $output->getFormatter()->setStyle('success', new OutputFormatterStyle('green'));
        if (!$output->isDecorated() && !defined('NO_ANSI')) {
            define('NO_ANSI', 'true');
        }
        $selector = $input->getArgument('selector');
        $selectExpression = is_array($selector) ? implode(',', $selector) : $selector;

        if (empty($selectExpression)) {
            if (count($this->deployer->hosts) === 0) {
                $hosts = [localhost(Localhost::extraordinary)];
            } else if (count($this->deployer->hosts) === 1) {
                $hosts = $this->deployer->hosts->all();
            } else if ($input->isInteractive()) {
                $hostsAliases = [];
                foreach ($this->deployer->hosts as $host) {
                    $hostsAliases[] = $host->getAlias();
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
                    return in_array($host->getAlias(), $answer, true);
                });
            }
        } else {
            $hosts = $this->deployer->selector->select($selectExpression);
        }

        if (empty($hosts)) {
            $message = 'No host selected.';
            if (!empty($selectExpression)) {
                $message .= " Please, check your selector:\n\n    $selectExpression";
            }
            throw new Exception($message);
        }

        return $hosts;
    }
}

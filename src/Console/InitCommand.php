<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Initializer\Initializer;
use Deployer\Initializer\Template\CakeTemplate;
use Deployer\Initializer\Template\CodeIgniterTemplate;
use Deployer\Initializer\Template\CommonTemplate;
use Deployer\Initializer\Template\DrupalTemplate;
use Deployer\Initializer\Template\LaravelTemplate;
use Deployer\Initializer\Template\SymfonyTemplate;
use Deployer\Initializer\Template\Typo3Template;
use Deployer\Initializer\Template\Yii2AdvancedAppTemplate;
use Deployer\Initializer\Template\Yii2BasicAppTemplate;
use Deployer\Initializer\Template\YiiTemplate;
use Deployer\Initializer\Template\ZendTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * The command for initialize Deployer system in your project
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class InitCommand extends Command
{
    /**
     * @var Initializer
     */
    private $initializer;

    /**
     * @var array
     */
    private $availableTemplates;

    /**
     * Construct
     *
     * @param string $name
     */
    public function __construct($name = null)
    {
        $this->initializer = $this->createInitializer();
        $this->availableTemplates = $this->initializer->getTemplateNames();

        parent::__construct($name);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize deployer in your project')
            ->addOption('template', 't', InputOption::VALUE_OPTIONAL, 'The template of you project. Available templates: ' . implode(', ', $this->availableTemplates))
            ->addOption('directory', null, InputOption::VALUE_OPTIONAL, 'The directory for create "deploy.php" file', getcwd())
            ->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'The file name. Default "deploy.php"', 'deploy.php');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $template = $input->getOption('template');
        $directory = $input->getOption('directory');
        $file = $input->getOption('filename');
        $params = [];

        if ($template === null) {
            $io = new SymfonyStyle($input, $output);
            $helper = $this->getHelper('question');
            $formatter = $this->getHelper('formatter');

            // Welcome message
            $output->writeln([
                '',
                $formatter->formatBlock('Welcome to the Deployer config generator', 'bg=blue;fg=white', true),
                '',
            ]);

            $io->text([
                'This utility will walk you through creating a deploy.php file.',
                'It only covers the most common items, and tries to guess sensible defaults.',
                '',
                'Press ^C at any time to quit.',
            ]);

            // Project type
            $template = $io->choice('Please select your project type', $this->availableTemplates, 'Common');

            // Repo
            $default = false;
            try {

                if (method_exists('Symfony\Component\Process\Process', 'fromShellCommandline')) {
                    $process = Process::fromShellCommandline('git remote get-url origin');
                } else {
                    $process = new Process('git remote get-url origin');
                }
                $default = $process
                    ->mustRun()
                    ->getOutput();
                $default = trim($default);
            } catch (RuntimeException $e) {
                // pass
            }
            $params['repository'] = $io->ask('Repository', $default);

            // Privacy
            $io->text([
                'Contribute to the Deployer Development',
                '',
                'In order to help development and improve the features in Deployer,',
                'Deployer has a setting for usage data collection. This function',
                'collects anonymous usage data and sends it to Deployer. The data is',
                'used in Deployer development to get reliable statistics on which',
                'features are used (or not used). The information is not traceable',
                'to any individual or organization. Participation is voluntary,',
                'and you can change your mind at any time.',
                '',
                'Anonymous usage data contains Deployer version, php version, os type,',
                'name of the command being executed and whether it was successful or not,',
                'exception class name, count of hosts and anonymized project hash.',
                '',
                'If you would like to allow us to gather this information and help',
                'us develop a better tool, please add the code below.',
                '',
                "    <fg=white>set(<fg=cyan>'allow_anonymous_stats'</fg=cyan>, <fg=magenta;options=bold>true</fg=magenta;options=bold>);</fg=white>",
                '',
                'This function will not affect the performance of Deployer as',
                'the data is insignificant and transmitted in a separate process.',
            ]);

            $params['allow_anonymous_stats'] = $GLOBALS['allow_anonymous_stats'] = $io->confirm('Do you confirm?');
        }

        $filePath = $this->initializer->initialize($template, $directory, $file, $params);

        $output->writeln(sprintf(
            '<info>Successfully created:</info> <comment>%s</comment>',
            $filePath
        ));

        return 0;
    }

    /**
     * Create a initializer system
     *
     * @return Initializer
     */
    private function createInitializer()
    {
        $initializer = new Initializer();

        $initializer->addTemplate('Common', new CommonTemplate());
        $initializer->addTemplate('Laravel', new LaravelTemplate());
        $initializer->addTemplate('Symfony', new SymfonyTemplate());
        $initializer->addTemplate('Yii', new YiiTemplate());
        $initializer->addTemplate('Yii2 Basic App', new Yii2BasicAppTemplate());
        $initializer->addTemplate('Yii2 Advanced App', new Yii2AdvancedAppTemplate());
        $initializer->addTemplate('Zend Framework', new ZendTemplate());
        $initializer->addTemplate('CakePHP', new CakeTemplate());
        $initializer->addTemplate('CodeIgniter', new CodeIgniterTemplate());
        $initializer->addTemplate('Drupal', new DrupalTemplate());
        $initializer->addTemplate('TYPO3', new Typo3Template());

        return $initializer;
    }
}

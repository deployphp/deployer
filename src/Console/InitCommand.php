<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Component\Initializer\Initializer;
use Deployer\Component\Initializer\Template\CakeTemplate;
use Deployer\Component\Initializer\Template\CodeIgniterTemplate;
use Deployer\Component\Initializer\Template\CommonTemplate;
use Deployer\Component\Initializer\Template\DrupalTemplate;
use Deployer\Component\Initializer\Template\LaravelTemplate;
use Deployer\Component\Initializer\Template\SymfonyTemplate;
use Deployer\Component\Initializer\Template\Typo3Template;
use Deployer\Component\Initializer\Template\Yii2AdvancedAppTemplate;
use Deployer\Component\Initializer\Template\Yii2BasicAppTemplate;
use Deployer\Component\Initializer\Template\YiiTemplate;
use Deployer\Component\Initializer\Template\ZendTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class InitCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize deployer in your project')
            ->addOption('template', 't', InputOption::VALUE_OPTIONAL, 'The template of you project')
            ->addOption('filepath', null, InputOption::VALUE_OPTIONAL, 'The file path (default "deploy.php")', 'deploy.php');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');
        $initializer = new Initializer();
        $template = $input->getOption('template');
        $filepath = $input->getOption('filepath');

        if (file_exists($filepath)) {
            $output->writeln([
                $formatter->formatBlock(
                    sprintf('The file "%s" already exist.', $filepath),
                    'bg=red;fg=white', true
                ),
            ]);
            return 2;
        }

        $project = 'my_project';
        $repository = '';
        $hosts = [];
        $allow = true;

        if ($template === null) {
            $io = new SymfonyStyle($input, $output);

            // Welcome message
            $output->writeln("
  _____             _
 |  __ \           | |
 | |  | | ___ _ __ | | ___  _   _  ___ _ __
 | |  | |/ _ \ '_ \| |/ _ \| | | |/ _ \ '__|
 | |__| |  __/ |_) | | (_) | |_| |  __/ |
 |_____/ \___| .__/|_|\___/ \__, |\___|_|
             | |             __/ |
             |_|            |___/
");

            $io->text([
                'Welcome to the Deployer config generator.',
                'This utility will walk you through creating a deploy.php file.',
                '',
                'Press ^C at any time to quit.',
            ]);

            // Yes?
            $io->confirm('Continue?');

            // Template
            $recipes = $initializer->getRecipes();
            $template = $io->choice('Select project template', $recipes, 'common');

            // Repo
            $default = false;
            try {
                $process = Process::fromShellCommandline('git remote get-url origin');
                $default = $process->mustRun()->getOutput();
                $default = trim($default);
            } catch (RuntimeException $e) {
            }
            $repository = $io->ask('Repository', $default);

            // Repo
            $default = false;
            try {
                $process = Process::fromShellCommandline('basename "$PWD"');
                $default = $process->mustRun()->getOutput();
                $default = trim($default);
            } catch (RuntimeException $e) {
            }
            $project = $io->ask('Project name', $default);

            // Hosts
            $hosts = explode(',', $io->ask('Hosts (comma separated)', 'deployer.org'));

            // Privacy
            $io->text(<<<TEXT
    <info>Contribute to the Deployer Development</info>

Help development and improve features of Deployer by 
an optional usage data collection program. This program
collects anonymous usage data and sends it to Deployer. 
The data is used in Deployer development to get reliable
statistics on which features are used (or not used). The
information is not traceable to any individual or 
organization. Participation is voluntary, and you can 
change your mind at any time.

Anonymous usage data contains Deployer version, PHP version, 
OS type, name of the command being executed and whether 
it was successful or not, count of hosts and anonymized 
project hash. This program will not affect the performance 
of Deployer as the data is insignificant and transmitted 
in a separate process.

We appreciate your involvement!
TEXT);

            $allow = $io->confirm('Do you confirm?');
        }


        file_put_contents($filepath, $initializer->getTemplate($template, $project, $repository, $hosts, $allow));

        $output->writeln(sprintf(
            '<info>Successfully created</info> <comment>%s</comment>',
            $filepath
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

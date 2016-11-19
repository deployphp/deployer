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
use Deployer\Initializer\Template\YiiTemplate;
use Deployer\Initializer\Template\ZendTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

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
            ->setDescription('Initialize deployer system in your project')
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

        if ($template === null) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Please select your project type (defaults to common):',
                $this->availableTemplates,
                0
            );
            $question->setErrorMessage('Project type %s is invalid.');

            $template = $helper->ask($input, $output, $question);
        }

        $filePath = $this->initializer->initialize($template, $directory, $file);

        $output->writeln(sprintf(
            '<info>Successfully created:</info> <comment>%s</comment>',
            $filePath
        ));
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
        $initializer->addTemplate('Zend Framework', new ZendTemplate());
        $initializer->addTemplate('CakePHP', new CakeTemplate());
        $initializer->addTemplate('CodeIgniter', new CodeIgniterTemplate());
        $initializer->addTemplate('Drupal', new DrupalTemplate());

        return $initializer;
    }
}

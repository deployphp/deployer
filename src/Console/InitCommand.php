<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Initializer\Initializer;
use Deployer\Initializer\Template\CommonTemplate;
use Deployer\Initializer\Template\ComposerTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     * Construct
     *
     * @param string $name
     */
    public function __construct($name = null)
    {
        $this->initializer = $this->createInitializer();

        parent::__construct($name);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $availableTemplates = implode(', ', $this->initializer->getTemplateNames());

        $this
            ->setName('init')
            ->setDescription('Initialize deployer system in your project.')
            ->addArgument('template', InputArgument::REQUIRED, 'The template of you project. Available templates: ' . $availableTemplates)
            ->addOption('directory', null, InputOption::VALUE_OPTIONAL, 'The directory for create "deploy.php" file.', getcwd())
            ->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'The file name. Default "deploy.php"', 'deploy.php');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $template = $input->getArgument('template');
        $directory = $input->getOption('directory');
        $file = $input->getOption('filename');

        $filePath = $this->initializer->initialize($template, $directory, $file);

        $output->writeln(sprintf(
            '<comment>Successfully create deployer configuration: <info>%s</info></comment>',
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

        $initializer->addTemplate('common', new CommonTemplate());
        $initializer->addTemplate('composer', new ComposerTemplate());

        return $initializer;
    }
}

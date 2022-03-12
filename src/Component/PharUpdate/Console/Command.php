<?php declare(strict_types=1);

namespace Deployer\Component\PharUpdate\Console;

use Deployer\Component\PharUpdate\Manager;
use LogicException;
use Symfony\Component\Console\Command\Command as Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Manages updating or upgrading the Phar.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Command extends Base
{
    /**
     * Disable the ability to upgrade?
     *
     * @var boolean
     */
    private $disableUpgrade = false;

    /**
     * The manifest file URI.
     *
     * @var string
     */
    private $manifestUri;

    /**
     * The running file (the Phar that will be updated).
     *
     * @var string
     */
    private $runningFile;

    /**
     * @param string $name The command name.
     * @param boolean $disable Disable upgrading?
     */
    public function __construct(string $name, bool $disable = false)
    {
        $this->disableUpgrade = $disable;

        parent::__construct($name);
    }

    /**
     * Sets the manifest URI.
     *
     * @param string $uri The URI.
     */
    public function setManifestUri(string $uri)
    {
        $this->manifestUri = $uri;
    }

    /**
     * Sets the running file (the Phar that will be updated).
     *
     * @param string $file The file name or path.
     */
    public function setRunningFile(string $file): void
    {
        $this->runningFile = $file;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Updates the application.');
        $this->addOption(
            'pre',
            'p',
            InputOption::VALUE_NONE,
            'Allow pre-release updates.'
        );
        $this->addOption(
            'redo',
            'r',
            InputOption::VALUE_NONE,
            'Redownload update if already using current version.'
        );

        if (false === $this->disableUpgrade) {
            $this->addOption(
                'upgrade',
                'u',
                InputOption::VALUE_NONE,
                'Upgrade to next major release, if available.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $this->manifestUri) {
            throw new LogicException(
                'No manifest URI has been configured.'
            );
        }

        $output->writeln('Looking for updates...');

        /** @var Helper */
        $pharUpdate = $this->getHelper('phar-update');
        /** @var Manager $manager */
        $manager = $pharUpdate->getManager($this->manifestUri);
        $manager->setRunningFile($this->runningFile);

        if ($manager->update(
            $this->getApplication()->getVersion(),
            $this->disableUpgrade ?: (false === $input->getOption('upgrade')),
            $input->getOption('pre')
        )) {
            $output->writeln('<info>Update successful!</info>');
        } else {
            $output->writeln('<comment>Already up-to-date.</comment>');
        }

        // Force exit to prevent warnings
        die(0);
    }
}

<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Deployer;
use Deployer\Importer\Importer;
use Deployer\Utility\Httpie;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

class ImportCommand extends Command
{
    use CommandCommon;

    private InputInterface $input;
    private OutputInterface $output;

    protected function configure()
    {
        $this
            ->setName('import')
            ->setDescription('Import a remote recipe')
            ->addOption('mode', 'm', InputOption::VALUE_REQUIRED, 'Can be either url or composer')
            ->addArgument('path', InputArgument::REQUIRED, 'Recipe file (can be URL or a path in the repo when using composer mode)')
            ->addArgument('package', InputArgument::OPTIONAL, 'Composer package name (can have an appended version string)')
            ->addArgument('repository', InputArgument::OPTIONAL, 'Composer package repository url')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');
        $package = $input->getArgument('package');
        $repository = $input->getArgument('repository');

        if(!Deployer::isWorker()) { // is worker is not returning the correct value, as this command is runnning before the symfony console is initialized and running
            // maybe the question should be asked only once (save the result), or not even be asked at all?
            if (!$io->askQuestion(new ConfirmationQuestion("<question>Do you really want to trust this remote recipe: $path?</question>", true))) {
                return 1;
            }
        }

        if(Importer::isUrl($path)) {
            $this->importUrl($path);
        }

        elseif(Importer::isRepo($path)) {
            $this->importComposer($path, $package, $repository);
        }
        else {
            throw new \Exception("Unrecognized path $path, make sure its a valid URL or a valid 'composer/package'");
        }

        return 0;
    }

    protected function importUrl(string $path)
    {
        if ($data = Httpie::get($path)->send()) {
            $tmpfname = tempnam("/tmp", "deployer_remote_recipe").".php";
            $tmpfhandle = fopen($tmpfname, "w");
            fwrite($tmpfhandle, $data);
            fclose($tmpfhandle);
            Deployer::get()->importer->import($tmpfname);
        } else {
            throw new \Exception("Could not download $path for import.");
        }
    }

    protected function importComposer(string $path, string $package, string $repository = null)
    {

        if(!$this->composerJsonExists()) {
            try {
                $process = Process::fromShellCommandline("composer init --no-interaction --name deployer/project", dirname(DEPLOYER_DEPLOY_FILE));
                $output = trim($process->mustRun()->getOutput());
            } catch (RuntimeException $e) {
                throw new \Exception($e->getMessage());
            }
            echo "Initialized composer.json for you\n";
        }

        if($repository) {
            $repoName = "deployer/".parse_url($repository, PHP_URL_HOST);

            try {
                $process = Process::fromShellCommandline("composer config repositories.$repoName composer $repository", dirname(DEPLOYER_DEPLOY_FILE));
                $output = trim($process->mustRun()->getOutput());
            } catch (RuntimeException $e) {
                throw new \Exception($e->getMessage());
            }
            if ($this->output->isVerbose()) {
                echo "Added repository to composer.json\n";
            }
        }

        try {
            $process = Process::fromShellCommandline("composer require --dev --no-plugins \"$package\"", dirname(DEPLOYER_DEPLOY_FILE));
            $output = trim($process->mustRun()->getOutput());
        } catch (RuntimeException $e) {
            throw new \Exception($e->getMessage());
        }
        if ($this->output->isVerbose()) {
            echo "Added require-dev package to composer.json\n";
        }

        list($packageWithoutVersion, $version) = explode(":", $package);
        $target = dirname(DEPLOYER_DEPLOY_FILE) . "/vendor/".$packageWithoutVersion."/".$path;
        if(file_exists($target)) {
            Importer::import($target);
        } else {
            throw new \Exception("Could not find imported composer file in ".$target);
        }
    }

    protected function getComposerJsonFile()
    {
        return dirname(DEPLOYER_DEPLOY_FILE) . "/composer.json";
    }

    private function composerJsonExists()
    {
        return file_exists($this->getComposerJsonFile());
    }

}

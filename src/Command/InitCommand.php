<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

class InitCommand extends Command
{
    use CommandCommon;

    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize deployer in your project')
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Recipe path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $recipePath = $input->getOption('path');

        // Welcome message
        if (getenv('COLORTERM') === 'truecolor') {
            $output->write("
 \e[38;2;94;231;223m█\e[39m\e[38;2;95;230;227m█\e[39m\e[38;2;97;228;230m█\e[39m\e[38;2;98;222;229m█\e[39m\e[38;2;99;217;228m█\e[39m\e[38;2;100;212;228m█\e[39m\e[38;2;102;207;227m█\e[39m\e[38;2;103;202;227m█\e[39m\e[38;2;104;197;226m█\e[39m\e[38;2;105;193;225m█\e[39m\e[38;2;106;188;225m█\e[39m\e[38;2;108;184;224m█\e[39m\e[38;2;109;180;223m█\e[39m\e[38;2;110;176;223m█\e[39m\e[38;2;111;172;222m█\e[39m\e[38;2;112;168;222m█\e[39m\e[38;2;113;164;221m█\e[39m\e[38;2;115;161;220m█\e[39m\e[38;2;116;157;220m█\e[39m\e[38;2;117;154;219m█\e[39m\e[38;2;118;151;218m█\e[39m\e[38;2;119;148;218m█\e[39m\e[38;2;120;145;217m█\e[39m\e[38;2;121;142;216m█\e[39m\e[38;2;122;139;216m█\e[39m\e[38;2;123;137;215m█\e[39m\e[38;2;124;134;215m█\e[39m\e[38;2;126;132;214m█\e[39m\e[38;2;127;130;213m█\e[39m\e[38;2;128;128;213m█\e[39m\e[38;2;132;129;212m█\e[39m\e[38;2;136;130;211m█\e[39m\e[38;2;139;131;211m█\e[39m\e[38;2;143;132;210m█\e[39m\e[38;2;147;133;210m█\e[39m\e[38;2;150;134;209m█\e[39m\e[38;2;153;135;208m█\e[39m\e[38;2;157;136;208m█\e[39m\e[38;2;160;137;207m█\e[39m\e[38;2;163;138;206m█\e[39m\e[38;2;166;138;206m█\e[39m\e[38;2;168;139;205m█\e[39m\e[38;2;171;140;205m█\e[39m\e[38;2;173;141;204m█\e[39m\e[38;2;176;142;203m█\e[39m\e[38;2;178;143;203m█\e[39m\e[38;2;180;144;202m█\e[39m
");
        }

        $io->text([
            'Welcome to the <fg=cyan>Deployer</fg=cyan> config generator.',
            '',
            'Press ^C at any time to quit.',
        ]);

        // Yes?
        $language = $io->choice('Select recipe language', ['php', 'yaml'], 'php');
        if (empty($recipePath)) {
            $recipePath = "deploy.$language";
        }

        // Avoid accidentally override of existing file.
        if (file_exists($recipePath)) {
            $io->warning("$recipePath already exists");
            if (!$io->confirm("Do you want to override the existing file?", false)) {
                $io->block('👍🏻');
                exit(1);
            }
        }

        // Template
        $template = $io->choice('Select project template', $this->recipes(), 'common');

        // Repo
        $default = '';
        try {
            $process = Process::fromShellCommandline('git remote get-url origin');
            $default = $process->mustRun()->getOutput();
            $default = trim($default);
        } catch (RuntimeException $e) {
        }
        $repository = $io->ask('Repository', $default);

        // Guess host
        if (preg_match('/github.com:(?<org>[A-Za-z0-9_.\-]+)\//', $repository, $m)) {
            $org = $m['org'];
            $tempHostFile = tempnam(sys_get_temp_dir(), 'temp-host-file');
            $php = new PhpProcess(<<<EOF
<?php
\$ch = curl_init('https://api.github.com/orgs/$org');
curl_setopt(\$ch, CURLOPT_USERAGENT, 'Deployer');
curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt(\$ch, CURLOPT_MAXREDIRS, 10);
curl_setopt(\$ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt(\$ch, CURLOPT_TIMEOUT, 5);
\$result = curl_exec(\$ch);
curl_close(\$ch);
\$json = json_decode(\$result);
\$host = parse_url(\$json->blog, PHP_URL_HOST);
file_put_contents('$tempHostFile', \$host);
EOF);
            $php->start();
        }

        // Project
        $default = '';
        try {
            $process = Process::fromShellCommandline('basename "$PWD"');
            $default = $process->mustRun()->getOutput();
            $default = trim($default);
        } catch (RuntimeException $e) {
        }
        $project = $io->ask('Project name', $default);

        // Hosts
        $host = null;
        if (isset($tempHostFile)) {
            $host = file_get_contents($tempHostFile);
        }
        $hostsString = $io->ask('Hosts (comma separated)', $host);
        if ($hostsString !== null) {
            $hosts = explode(',', $hostsString);
        } else {
            $hosts = [];
        }

        file_put_contents($recipePath, $this->$language($template, $project, $repository, $hosts));

        $this->telemetry();
        $output->writeln(sprintf(
            '<info>Successfully created</info> <comment>%s</comment>',
            $recipePath
        ));
        return 0;
    }

    private function php(string $template, string $project, string $repository, array $hosts): string
    {
        $h = "";
        foreach ($hosts as $host) {
            $h .= "host('{$host}');\n";
        }

        return <<<PHP
<?php
namespace Deployer;

require 'recipe/$template.php';

// Config

set('application', '{$project}');
set('deploy_path', '~/{{application}}');
set('repository', '{$repository}');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

{$h}
// Tasks

task('build', function () {
    cd('{{release_path}}');
    run('npm run build');
});

after('deploy:failed', 'deploy:unlock');

PHP;
    }

    private function yaml(string $template, string $project, string $repository, array $hosts): string
    {
        $h = "";
        foreach ($hosts as $host) {
            $h .= "  $host:\n    deploy_path: '~/{{application}}'\n";
        }

        $additionalConfigs = $this->getAdditionalConfigs($template);

        return <<<YAML
import: 
    - recipe/$template.php

config:
  application: '$project'
  repository: '$repository'
{$additionalConfigs}
hosts:
{$h}
tasks:
  build:
    script:
      - 'cd {{release_path}} && npm run build'

after:
  deploy:failed: deploy:unlock

YAML;
    }

    private function getAdditionalConfigs(string $template): string
    {
        if ($template !== 'common') {
            return '';
        }

        return <<<YAML
  shared_files:
    - .env
  shared_dirs:
    - uploads
  writable_dirs:
    - uploads
  
YAML;
    }

    private function recipes(): array
    {
        $recipes = [];
        $dir = new \DirectoryIterator(__DIR__ . '/../../recipe');
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDot()) {
                continue;
            }
            if ($fileinfo->isDir()) {
                continue;
            }

            $recipe = pathinfo($fileinfo->getFilename(), PATHINFO_FILENAME);

            if ($recipe === 'README') {
                continue;
            }

            $recipes[] = $recipe;
        }

        sort($recipes);
        return $recipes;
    }
}

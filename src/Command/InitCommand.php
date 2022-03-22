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
        if (getenv('COLORTERM') === 'truecolor') {
            $output->write(<<<EOF
╭───────────────────────────────────────╮
│                                       │
│                                       │
│    \e[38;2;94;231;223m_\e[39m\e[38;2;95;231;226m_\e[39m\e[38;2;96;230;228m_\e[39m\e[38;2;96;229;230m_\e[39m          \e[38;2;97;226;230m_\e[39m                    │
│   \e[38;2;98;223;229m|\e[39m    \e[38;2;98;220;229m\\\e[39m \e[38;2;99;216;228m_\e[39m\e[38;2;100;213;228m_\e[39m\e[38;2;101;210;228m_\e[39m \e[38;2;101;208;227m_\e[39m\e[38;2;102;205;227m_\e[39m\e[38;2;103;202;227m_\e[39m\e[38;2;104;199;226m|\e[39m \e[38;2;104;196;226m|\e[39m\e[38;2;105;194;225m_\e[39m\e[38;2;106;191;225m_\e[39m\e[38;2;106;188;225m_\e[39m \e[38;2;107;186;224m_\e[39m \e[38;2;108;183;224m_\e[39m \e[38;2;109;181;224m_\e[39m\e[38;2;109;178;223m_\e[39m\e[38;2;110;176;223m_\e[39m \e[38;2;111;174;222m_\e[39m\e[38;2;111;171;222m_\e[39m\e[38;2;112;169;222m_\e[39m    │
│   \e[38;2;113;167;221m|\e[39m  \e[38;2;113;165;221m|\e[39m  \e[38;2;114;163;221m|\e[39m \e[38;2;115;160;220m-\e[39m\e[38;2;115;158;220m_\e[39m\e[38;2;116;156;219m|\e[39m \e[38;2;117;155;219m.\e[39m \e[38;2;117;153;219m|\e[39m \e[38;2;118;151;218m|\e[39m \e[38;2;119;149;218m.\e[39m \e[38;2;119;147;218m|\e[39m \e[38;2;120;145;217m|\e[39m \e[38;2;121;144;217m|\e[39m \e[38;2;121;142;216m-\e[39m\e[38;2;122;140;216m_\e[39m\e[38;2;123;139;216m|\e[39m  \e[38;2;123;137;215m_\e[39m\e[38;2;124;136;215m|\e[39m   │
│   \e[38;2;124;134;215m|\e[39m\e[38;2;125;133;214m_\e[39m\e[38;2;126;132;214m_\e[39m\e[38;2;126;130;214m_\e[39m\e[38;2;127;129;213m_\e[39m\e[38;2;127;128;213m/\e[39m\e[38;2;130;128;212m|\e[39m\e[38;2;132;129;212m_\e[39m\e[38;2;134;129;212m_\e[39m\e[38;2;137;130;211m_\e[39m\e[38;2;139;131;211m|\e[39m  \e[38;2;141;131;211m_\e[39m\e[38;2;143;132;210m|\e[39m\e[38;2;145;132;210m_\e[39m\e[38;2;147;133;209m|\e[39m\e[38;2;149;133;209m_\e[39m\e[38;2;151;134;209m_\e[39m\e[38;2;153;135;208m_\e[39m\e[38;2;155;135;208m|\e[39m\e[38;2;157;136;208m_\e[39m  \e[38;2;159;136;207m|\e[39m\e[38;2;161;137;207m_\e[39m\e[38;2;162;137;206m_\e[39m\e[38;2;164;138;206m_\e[39m\e[38;2;166;139;206m|\e[39m\e[38;2;167;139;205m_\e[39m\e[38;2;169;140;205m|\e[39m     │
│             \e[38;2;170;140;205m|\e[39m\e[38;2;172;141;204m_\e[39m\e[38;2;173;141;204m|\e[39m       \e[38;2;175;142;203m|\e[39m\e[38;2;176;142;203m_\e[39m\e[38;2;177;143;203m_\e[39m\e[38;2;179;143;202m_\e[39m\e[38;2;180;144;202m|\e[39m           │
│                                       │
│                                       │
╰───────────────────────────────────────╯

EOF
            );
        } else {
            $output->write(<<<EOF
╭───────────────────────────────────────╮
│                                       │
│                                       │
│    ____          _                    │
│   |    \ ___ ___| |___ _ _ ___ ___    │
│   |  |  | -_| . | | . | | | -_|  _|   │
│   |____/|___|  _|_|___|_  |___|_|     │
│             |_|       |___|           │
│                                       │
│                                       │
╰───────────────────────────────────────╯

EOF
            );
        }

        $io = new SymfonyStyle($input, $output);
        $recipePath = $input->getOption('path');

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
EOF
            );
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
            $h .= "host('{$host}')\n" .
                "    ->set('remote_user', 'deployer')\n" .
                "    ->set('deploy_path', '~/{$project}');\n";
        }

        return <<<PHP
<?php
namespace Deployer;

require 'recipe/$template.php';

// Config

set('repository', '{$repository}');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

{$h}
// Hooks

after('deploy:failed', 'deploy:unlock');

PHP;
    }

    private function yaml(string $template, string $project, string $repository, array $hosts): string
    {
        $h = "";
        foreach ($hosts as $host) {
            $h .= "  $host:\n".
                "    remote_user: deployer\n" .
                "    deploy_path: '~/{$project}'\n";
        }

        $additionalConfigs = $this->getAdditionalConfigs($template);

        return <<<YAML
import: 
  - recipe/$template.php

config:
  repository: '$repository'
$additionalConfigs
hosts:
$h
tasks:
  build:
    - run: uptime  

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

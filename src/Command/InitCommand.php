<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Component\Initializer\Initializer;
use Deployer\Utility\Httpie;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use function Deployer\Support\fork;

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
        $output->write("
            \e[38;2;94;231;223m╔\e[39m\e[38;2;95;230;227m╦\e[39m\e[38;2;96;230;230m╗\e[39m\e[38;2;97;226;230m┌\e[39m\e[38;2;98;221;229m─\e[39m\e[38;2;99;217;228m┐\e[39m\e[38;2;100;213;228m┌\e[39m\e[38;2;101;209;227m─\e[39m\e[38;2;102;205;227m┐\e[39m\e[38;2;103;202;226m┬\e[39m  \e[38;2;104;198;226m┌\e[39m\e[38;2;105;194;225m─\e[39m\e[38;2;106;191;225m┐\e[39m\e[38;2;107;187;224m┬\e[39m \e[38;2;108;184;224m┬\e[39m\e[38;2;109;180;223m┌\e[39m\e[38;2;110;177;223m─\e[39m\e[38;2;110;174;222m┐\e[39m\e[38;2;111;171;222m┬\e[39m\e[38;2;112;168;221m─\e[39m\e[38;2;113;165;221m┐\e[39m
             \e[38;2;114;162;220m║\e[39m\e[38;2;115;159;220m║\e[39m\e[38;2;116;157;219m├\e[39m\e[38;2;117;154;219m┤\e[39m \e[38;2;118;151;218m├\e[39m\e[38;2;119;149;218m─\e[39m\e[38;2;119;147;217m┘\e[39m\e[38;2;120;144;217m│\e[39m  \e[38;2;121;142;216m│\e[39m \e[38;2;122;140;216m│\e[39m\e[38;2;123;138;215m└\e[39m\e[38;2;124;136;215m┬\e[39m\e[38;2;125;134;214m┘\e[39m\e[38;2;125;132;214m├\e[39m\e[38;2;126;130;213m┤\e[39m \e[38;2;127;129;213m├\e[39m\e[38;2;129;128;212m┬\e[39m\e[38;2;132;129;212m┘\e[39m
            \e[38;2;135;130;211m═\e[39m\e[38;2;138;130;211m╩\e[39m\e[38;2;141;131;210m╝\e[39m\e[38;2;144;132;210m└\e[39m\e[38;2;147;133;209m─\e[39m\e[38;2;150;134;209m┘\e[39m\e[38;2;152;134;208m┴\e[39m  \e[38;2;155;135;208m┴\e[39m\e[38;2;158;136;207m─\e[39m\e[38;2;160;137;207m┘\e[39m\e[38;2;162;137;206m└\e[39m\e[38;2;165;138;206m─\e[39m\e[38;2;167;139;205m┘\e[39m \e[38;2;169;140;205m┴\e[39m \e[38;2;171;140;204m└\e[39m\e[38;2;173;141;204m─\e[39m\e[38;2;175;142;203m┘\e[39m\e[38;2;177;143;203m┴\e[39m\e[38;2;178;143;202m└\e[39m\e[38;2;180;144;202m─\e[39m
    
 \e[38;2;94;231;223m█\e[39m\e[38;2;95;230;227m█\e[39m\e[38;2;97;228;230m█\e[39m\e[38;2;98;222;229m█\e[39m\e[38;2;99;217;228m█\e[39m\e[38;2;100;212;228m█\e[39m\e[38;2;102;207;227m█\e[39m\e[38;2;103;202;227m█\e[39m\e[38;2;104;197;226m█\e[39m\e[38;2;105;193;225m█\e[39m\e[38;2;106;188;225m█\e[39m\e[38;2;108;184;224m█\e[39m\e[38;2;109;180;223m█\e[39m\e[38;2;110;176;223m█\e[39m\e[38;2;111;172;222m█\e[39m\e[38;2;112;168;222m█\e[39m\e[38;2;113;164;221m█\e[39m\e[38;2;115;161;220m█\e[39m\e[38;2;116;157;220m█\e[39m\e[38;2;117;154;219m█\e[39m\e[38;2;118;151;218m█\e[39m\e[38;2;119;148;218m█\e[39m\e[38;2;120;145;217m█\e[39m\e[38;2;121;142;216m█\e[39m\e[38;2;122;139;216m█\e[39m\e[38;2;123;137;215m█\e[39m\e[38;2;124;134;215m█\e[39m\e[38;2;126;132;214m█\e[39m\e[38;2;127;130;213m█\e[39m\e[38;2;128;128;213m█\e[39m\e[38;2;132;129;212m█\e[39m\e[38;2;136;130;211m█\e[39m\e[38;2;139;131;211m█\e[39m\e[38;2;143;132;210m█\e[39m\e[38;2;147;133;210m█\e[39m\e[38;2;150;134;209m█\e[39m\e[38;2;153;135;208m█\e[39m\e[38;2;157;136;208m█\e[39m\e[38;2;160;137;207m█\e[39m\e[38;2;163;138;206m█\e[39m\e[38;2;166;138;206m█\e[39m\e[38;2;168;139;205m█\e[39m\e[38;2;171;140;205m█\e[39m\e[38;2;173;141;204m█\e[39m\e[38;2;176;142;203m█\e[39m\e[38;2;178;143;203m█\e[39m\e[38;2;180;144;202m█\e[39m
");

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
            fork(function () use ($org, $tempHostFile) {
                try {
                    ['blog' => $blog] = Httpie::get('https://api.github.com/orgs/' . $org)->getJson();
                    $host = parse_url($blog, PHP_URL_HOST);
                    file_put_contents($tempHostFile, $host);
                } catch (\Throwable $e) {
                    // ¯\_(ツ)_/¯
                }
            });
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
        $hosts = explode(',', $io->ask('Hosts (comma separated)', $host));

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

        return <<<YAML
import: 
    - recipe/$template.php

config:
  application: '$project'
  repository: '$repository'
  shared_files:
    - .env
  shared_dirs:
    - uploads
  writable_dirs:
    - uploads

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

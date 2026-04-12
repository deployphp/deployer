<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Maml\Maml;
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

    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Initialize deployer in your project')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Recipe path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
       // TODO

        $io = new SymfonyStyle($input, $output);
        $recipePath = $input->getOption('path');

        $language = $io->choice('Select recipe language', ['php', 'maml'], 'php');
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
            $php = new PhpProcess(
                <<<EOF
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
                    if (PHP_MAJOR_VERSION < 8) {
                        curl_close(\$ch);
                    }
                    \$json = json_decode(\$result);
                    \$host = parse_url(\$json->blog, PHP_URL_HOST);
                    file_put_contents('$tempHostFile', \$host);
                    EOF,
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


        $code = match ($language) {
            'php' => $this->php($template, $project, $repository, $hosts),
            'maml' => $this->maml($template, $project, $repository, $hosts),
            default => $default,
        };

        file_put_contents($recipePath, $code);

        $this->telemetry();
        $output->writeln(sprintf(
            '<info>Successfully created</info> <comment>%s</comment>',
            $recipePath,
        ));
        return 0;
    }

    private function php(string $template, string $project, string $repository, array $hosts): string
    {
        $h = "";
        foreach ($hosts as $host) {
            $h .= "host('{$host}')\n"
                . "    ->set('remote_user', 'deployer')\n"
                . "    ->set('deploy_path', '~/{$project}');\n";
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

    private function maml(string $template, string $project, string $repository, array $hosts): string
    {
        $recipe = [
            "import" => [
                "recipe/$template.php",
            ],
            "config" => [
                "repository" => "$repository",
            ],
            "hosts" => [],
            "tasks" => [
                "example" => [
                    [
                        "run" => "date",
                    ],
                ],
            ],
        ];

        foreach ($hosts as $host) {
            $recipe['hosts'][$host] = [
                "remote_user" => "deployer",
                "deploy_path" => "~/$project",
            ];
        }

        return Maml::stringify($recipe);
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

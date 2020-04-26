<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Component\Initializer;

class Initializer
{
    public function getRecipes()
    {
        $recipes = [];
        $dir = new \DirectoryIterator(__DIR__ . '/../../../recipe');
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

    public function getTemplate(string $template, string $project, string $repository, array $hosts, bool $allow): string
    {
        $h = "";
        foreach ($hosts as $host) {
            $h .= "host('{$host}');\n";
        }

        $dontTrack = $allow ? '' : "define('DONT_TRACK', 'ಠ_ಠ');\n";

        return <<<PHP
<?php
namespace Deployer;
{$dontTrack}
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
    run('cd {{release_path}} && npm run build');
});

task('build:upload', function () {
    upload('dist', '{{release_path}}/dist');
});

// If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

PHP;
    }
}

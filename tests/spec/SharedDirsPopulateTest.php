<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use spec\SpecTest;

use const __TEMP_DIR__;

class SharedDirsPopulateTest extends SpecTest
{
    public function testPopulateCopiesNewReleaseFilesWithoutOverwriting()
    {
        $repo = $this->createRepo('populate_repo', ['data/a.txt' => 'first']);
        $recipe = $this->writeRecipe('prod1', $repo, true);
        $deployPath = __TEMP_DIR__ . '/prod1';

        $this->depFile($recipe, 'deploy');
        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());
        self::assertFileExists($deployPath . '/shared/data/a.txt');
        self::assertSame('first', file_get_contents($deployPath . '/shared/data/a.txt'));

        // Simulate a new file being added to the repository over time.
        $this->addFileAndCommit($repo, 'data/b.txt', 'second');

        $this->depFile($recipe, 'deploy');
        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());

        self::assertFileExists($deployPath . '/shared/data/b.txt');
        self::assertSame('second', file_get_contents($deployPath . '/shared/data/b.txt'));
        self::assertFileExists($deployPath . '/current/data/b.txt');

        // Existing shared file must not be overwritten by the release content.
        self::assertSame('first', file_get_contents($deployPath . '/shared/data/a.txt'));
    }

    public function testPopulateDisabledByDefaultDoesNotSyncNewFiles()
    {
        $repo = $this->createRepo('no_populate_repo', ['data/a.txt' => 'first']);
        $recipe = $this->writeRecipe('prod2', $repo, false);
        $deployPath = __TEMP_DIR__ . '/prod2';

        $this->depFile($recipe, 'deploy');
        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());
        self::assertFileExists($deployPath . '/shared/data/a.txt');

        $this->addFileAndCommit($repo, 'data/b.txt', 'second');

        $this->depFile($recipe, 'deploy');
        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());

        self::assertFileDoesNotExist($deployPath . '/shared/data/b.txt');
    }

    private function createRepo(string $name, array $files): string
    {
        $dir = __TEMP_DIR__ . '/' . $name;
        mkdir($dir, 0777, true);

        foreach ($files as $path => $content) {
            $full = $dir . '/' . $path;
            if (!is_dir(dirname($full))) {
                mkdir(dirname($full), 0777, true);
            }
            file_put_contents($full, $content);
        }

        exec("cd $dir && git init 2>&1");
        exec("cd $dir && git config user.name 'Deployer Test' && git config user.email 'test@example.com'");
        exec("cd $dir && git add . && git commit -m 'init' 2>&1");

        return $dir;
    }

    private function addFileAndCommit(string $repo, string $path, string $content): void
    {
        $full = $repo . '/' . $path;
        if (!is_dir(dirname($full))) {
            mkdir(dirname($full), 0777, true);
        }
        file_put_contents($full, $content);

        exec("cd $repo && git add . && git commit -m 'add file' 2>&1");
    }

    private function writeRecipe(string $hostname, string $repository, bool $populate): string
    {
        $populateValue = $populate ? 'true' : 'false';
        $recipe = <<<PHP
<?php
namespace Deployer;
require 'recipe/common.php';
set('application', 'deployer');
set('repository', '$repository');
set('shared_dirs', ['data']);
set('shared_dirs_populate', $populateValue);
set('keep_releases', 5);
set('http_user', false);
localhost('$hostname');
task('deploy:vendors', function () {
});
PHP;
        $file = __TEMP_DIR__ . "/$hostname.php";
        file_put_contents($file, $recipe);

        return $file;
    }
}

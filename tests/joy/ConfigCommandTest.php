<?php

declare(strict_types=1);

namespace joy;

use Symfony\Component\Console\Output\OutputInterface;

class ConfigCommandTest extends JoyTest
{
    protected function recipe(): string
    {
        return <<<'PHP'
            <?php
            namespace Deployer;
            localhost('web')
                ->set('deploy_path', '/var/www');
            PHP;
    }

    protected function depConfig(string $format): int
    {
        $recipe = \__TEMP_DIR__ . '/' . get_called_class() . '.php';
        file_put_contents($recipe, $this->recipe());
        $this->init($recipe);
        return $this->tester->run([
            'config',
            'selector' => ['all'],
            '--file' => $recipe,
            '--format' => $format,
        ], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);
    }

    public function testConfigOutputYaml(): void
    {
        $this->depConfig('yaml');
        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('web:', $display);
        self::assertStringContainsString('deploy_path', $display);
    }

    public function testConfigOutputJson(): void
    {
        $this->depConfig('json');
        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('"web"', $display);
        self::assertStringContainsString('deploy_path', $display);
    }
}

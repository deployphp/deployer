<?php
namespace e2e;

use Deployer\Exception\Exception;

class ProvisionE2ETest extends AbstractE2ETest
{
    private const RECIPE = __DIR__ . '/recipe/provision.php';

    /**
     * @throws Exception
     */
    public function testProvisionRecipe(): void
    {
        $this->init(self::RECIPE);

        $this->tester->setInputs(['deployer']); // set input for deployer user pass

        $this->tester->run([
            'provision',
            '-f' => self::RECIPE,
            '-o' => [ 'firewall=false' ], // disable firewall provisioning in docker e2e environment
            'selector' => 'all'
        ]);

        $display = trim($this->tester->getDisplay());
        self::assertEquals(0, $this->tester->getStatusCode(), $display);

        self::assertStringContainsString('nginx version:', $display);
        self::assertStringContainsString('PHP 7', $display);
        self::assertStringContainsString('(cli)', $display);
        self::assertStringContainsString('(fpm-fcgi)', $display);
    }
}

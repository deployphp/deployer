<?php declare(strict_types=1);
namespace e2e;

class SymfonyBoilerplateE2ETest extends AbstractE2ETest
{
    private const RECIPE = __DIR__ . '/recipe/symfony-boilerplate.php';

    public function testDeploySymfonyBoilerplate(): void
    {
        $this->init(self::RECIPE);

        $this->tester->run([
            'deploy',
            '-f' => self::RECIPE,
            'selector' => 'all',
        ]);

        $display = trim($this->tester->getDisplay());
        self::assertEquals(0, $this->tester->getStatusCode(), $display);

        $siteContent = file_get_contents('http://server.test');
        $expectedSiteContent = "Hello, World!";
        self::assertStringContainsString($expectedSiteContent, $siteContent);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->tester) {
            $this->tester->run([
                'deploy:unlock',
                '-f' => self::RECIPE,
                'selector' => 'all',
            ]);
        }
    }
}

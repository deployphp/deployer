<?php declare(strict_types=1);
namespace e2e;

class LaravelBoilerplateE2ETest extends AbstractE2ETest
{
    private const RECIPE = __DIR__ . '/recipe/laravel-boilerplate.php';

    public function testDeployLaravelBoilerplate(): void
    {
        $this->tester->setTimeout(180)
            ->run([
                '-f' => self::RECIPE,
                'deploy',
                'all',
            ]);

        $display = trim($this->tester->getDisplay());
        self::assertEquals(0, $this->tester->getStatusCode(), $display);

        $siteContent = file_get_contents('http://server.test');
        $expectedSiteContent = "Laravel has wonderful, thorough documentation covering every aspect of the framework. Whether you are new to the framework or have previous experience with Laravel, we recommend reading all of the documentation from beginning to end.";
        self::assertStringContainsString($expectedSiteContent, $siteContent);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->tester) {
            $this->tester->run([
                '-f' => self::RECIPE,
                'deploy:unlock',
                'all',
            ]);
        }
    }
}

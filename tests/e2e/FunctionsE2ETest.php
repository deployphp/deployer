<?php declare(strict_types=1);
namespace e2e;

class FunctionsE2ETest extends AbstractE2ETest
{
    private const RECIPE = __DIR__ . '/recipe/functions.php';

    /**
     * @group e2e
     */
    public function testRunWithPlaceholders(): void
    {
        $this->tester->run([
            '-f' => self::RECIPE,
            'test:functions:run-with-placeholders',
            'all',
        ]);

        $display = trim($this->tester->getDisplay());

        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('placeholder {{bar}} xyz%', $display);
    }
}

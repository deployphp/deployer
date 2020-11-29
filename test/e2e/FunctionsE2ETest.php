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
        $this->init(self::RECIPE);

        $this->tester->run([
            'test:functions:run-with-placeholders',
            '-f' => self::RECIPE,
            'selector' => 'all',
        ], [ 'decorated' => false ]);

        $display = trim($this->tester->getDisplay());

        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertEquals('placeholder {{bar}} xyz%', $display);
    }
}

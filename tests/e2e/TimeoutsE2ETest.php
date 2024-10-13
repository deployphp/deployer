<?php

declare(strict_types=1);

namespace e2e;

class TimeoutsE2ETest extends AbstractE2ETest
{
    private const RECIPE = __DIR__ . '/recipe/timeouts.php';

    /**
     * @group e2e
     */
    public function testRunWithPlaceholders(): void
    {
        $this->tester->run([
            '-f' => self::RECIPE,
            'test:timeouts',
            'all',
            '-v',
        ]);

        $display = trim($this->tester->getDisplay());

        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('+timeout', $display);
    }
}

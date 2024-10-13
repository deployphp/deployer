<?php

declare(strict_types=1);

namespace e2e;

class MiscE2ETest extends AbstractE2ETest
{
    private const RECIPE = __DIR__ . '/recipe/misc.php';

    /**
     * @group e2e
     */
    public function testSudoWithPasswordEnteredInteractively(): void
    {
        // We're adding this to inputs, to have it passed with via the STDIN
        $this->tester->setInputs(['deployer']);

        $this->tester->run([
            '-f' => self::RECIPE,
            'test:misc:sudo-write-user',
            'all',
        ]);

        $display = trim($this->tester->getDisplay());

        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('Current user is: root', $display);
    }

    /**
     * @group e2e
     */
    public function testSudoWithPasswordProvidedViaArgument(): void
    {
        $this->tester->run([
            '-f' => self::RECIPE,
            'test:misc:sudo-write-user',
            '-o' => 'sudo_pass=deployer',
            'all',
        ]);

        $display = trim($this->tester->getDisplay());

        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('Current user is: root', $display);
    }
}

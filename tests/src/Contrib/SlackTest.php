<?php

declare(strict_types=1);

namespace Deployer\Contrib;

use Deployer\Deployer;
use Deployer\Utility\HttpResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\BufferedOutput;

use function Deployer\checkSlackAnswer;

#[Group('unit')]
class SlackTest extends TestCase
{
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $deployer = new Deployer(new Application());
        $this->output = new BufferedOutput();
        $deployer['output'] = $this->output;

        require_once __DIR__ . '/../../../contrib/slack.php';
    }

    public function testRejectsInvalidTokenResponse(): void
    {
        self::assertFalse(checkSlackAnswer(new HttpResponse('invalid_token', ['http_code' => 403])));
        self::assertStringContainsString('Invalid Slack token', $this->output->fetch());
    }

    public function testAcceptsSuccessfulResponse(): void
    {
        self::assertTrue(checkSlackAnswer(new HttpResponse('ok', ['http_code' => 200])));
        self::assertSame('', $this->output->fetch());
    }

    public function testRejectsInvalidTokenString(): void
    {
        self::assertFalse(checkSlackAnswer('invalid_token'));
        self::assertStringContainsString('Invalid Slack token', $this->output->fetch());
    }

    public function testAcceptsSuccessfulString(): void
    {
        self::assertTrue(checkSlackAnswer('ok'));
        self::assertSame('', $this->output->fetch());
    }
}

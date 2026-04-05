<?php

declare(strict_types=1);

namespace Deployer\Import;

use Deployer\Deployer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

class YamlRecipeTest extends TestCase
{
    private Deployer $deployer;

    public function setUp(): void
    {
        $console = new Application();
        $input = $this->createStub(Input::class);
        $output = $this->createStub(Output::class);

        $this->deployer = new Deployer($console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $output;
    }

    public function tearDown(): void
    {
        unset($this->deployer);
    }

    public function testImporterIgnoresYamlHiddenKeys(): void
    {
        $data = <<<EOL
            .base: &base
              remote_user: foo
              labels:
                stage: production

            hosts:
              acceptance:
                <<: *base
                labels:
                  stage: acceptance

              production:
                <<: *base
                remote_user: bar

              production.beta:
                <<: *base
            # test.yaml
            EOL;

        Import::import("data:text/yaml,$data");
        self::assertTrue($this->deployer->hosts->has('production'));
        self::assertTrue($this->deployer->hosts->has('acceptance'));
        self::assertTrue($this->deployer->hosts->has('production.beta'));
        self::assertEquals('acceptance', $this->deployer->hosts->get('acceptance')->getLabels()['stage']);
        self::assertEquals('production', $this->deployer->hosts->get('production')->getLabels()['stage']);
        self::assertEquals('foo', $this->deployer->hosts->get('acceptance')->getRemoteUser());
        self::assertEquals('bar', $this->deployer->hosts->get('production')->getRemoteUser());
    }
}

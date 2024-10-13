<?php

declare(strict_types=1);

namespace Deployer\Importer;

use Deployer\Deployer;
use PHPUnit\Framework\TestCase;

class ImporterTest extends TestCase
{
    private $previousInput;
    private $previousOutput;

    public function setUp(): void
    {
        $deployer = Deployer::get();
        $this->previousInput = $deployer->input;
        $this->previousOutput = $deployer->output;
    }

    public function tearDown(): void
    {
        Deployer::get()->input = $this->previousInput;
        Deployer::get()->output = $this->previousOutput;
    }

    public function testCanOneOverrideStaticMethod(): void
    {
        $extendedImporter = new class extends Importer {
            public static $config = [];

            protected static function config(array $config)
            {
                static::$config = $config;
            }
        };

        $data = <<<EOL
            config:
                foo: bar
            # test.yaml
            EOL;

        $extendedImporter::import("data:text/yaml,$data");

        static::assertSame(['foo' => 'bar'], $extendedImporter::$config);
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

        Importer::import("data:text/yaml,$data");
        self::assertTrue(Deployer::get()->hosts->has('production'));
        self::assertTrue(Deployer::get()->hosts->has('acceptance'));
        self::assertTrue(Deployer::get()->hosts->has('production.beta'));
        self::assertEquals('acceptance', Deployer::get()->hosts->get('acceptance')->getLabels()['stage']);
        self::assertEquals('production', Deployer::get()->hosts->get('production')->getLabels()['stage']);
        self::assertEquals('foo', Deployer::get()->hosts->get('acceptance')->getRemoteUser());
        self::assertEquals('bar', Deployer::get()->hosts->get('production')->getRemoteUser());
    }
}

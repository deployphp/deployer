<?php
declare(strict_types=1);

namespace Deployer\Importer;

use Deployer\Deployer;
use Deployer\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

class ImporterTest extends TestCase
{
    public function setUp(): void
    {
        $console = new Application();
        $input = $this->createMock(Input::class);
        $output = $this->createMock(Output::class);

        $deployer = new Deployer($console);
        $deployer->input = $input;
        $deployer->output = $output;
    }

    public function testCanOneOverrideStaticMethod(): void
    {
        $extendedImporter = new class extends Importer
        {
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

    public function testThrowsForInvalidConfig(): void
    {
        $data = <<<EOL
unknownProperty: some-string-value
EOL;
        $tmpFile = tempnam(sys_get_temp_dir(), 'deployer-') . '.yaml';
        file_put_contents($tmpFile, $data);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/' . basename($tmpFile) . '/');
        $this->expectExceptionMessageMatches('/The property unknownProperty is not defined and the definition does not allow additional properties/');

        Importer::import($tmpFile);
    }
}

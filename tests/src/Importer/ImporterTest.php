<?php
declare(strict_types=1);

namespace Deployer\Importer;

use Deployer\Deployer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImporterTest extends TestCase
{
    private $deployer;

    protected function setUp(): void
    {
        $console = new Application();
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $this->deployer = new Deployer($console, $input, $output);
    }

    protected function tearDown(): void
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
# test.yaml
EOL;

        Importer::import("data:text/yaml,$data");
        self::assertTrue(Deployer::get()->hosts->has('production'));
        self::assertTrue(Deployer::get()->hosts->has('acceptance'));
        self::assertEquals('acceptance', Deployer::get()->hosts->get('acceptance')->getLabels()['stage']);
        self::assertEquals('production', Deployer::get()->hosts->get('production')->getLabels()['stage']);
        self::assertEquals('foo', Deployer::get()->hosts->get('acceptance')->getRemoteUser());
        self::assertEquals('bar', Deployer::get()->hosts->get('production')->getRemoteUser());
    }
}

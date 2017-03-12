<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Stage;

use Deployer\Server\Environment;
use Deployer\Server\EnvironmentCollection;
use Deployer\Server\ServerCollection;
use PHPUnit\Framework\TestCase;

class StageStrategyTest extends TestCase
{
    public function testDefault()
    {
        $servers = new ServerCollection();
        $environments = new EnvironmentCollection();

        $stage = new StageStrategy($servers, $environments);

        $this->assertArrayHasKey('localhost', $stage->getHosts(null));
    }

    public function testWithoutStageAndNoDefault()
    {
        $servers = new ServerCollection();
        $servers['one'] = new \stdClass();
        $servers['two'] = new \stdClass();

        $environments = new EnvironmentCollection();
        $environments['one'] = new Environment();
        $environments['two'] = new Environment();
        $environments['two']->set('stages', ['prod']);

        $stage = new StageStrategy($servers, $environments);

        $this->assertEquals(['one' => $servers['one']], $stage->getHosts(null));
    }

    public function testWithoutStageAndHasDefault()
    {
        $servers = new ServerCollection();
        $servers['one'] = new \stdClass();
        $servers['two'] = new \stdClass();

        $environments = new EnvironmentCollection();
        $environments['one'] = new Environment();
        $environments['two'] = new Environment();
        $environments['two']->set('stages', ['prod']);

        $stage = new StageStrategy($servers, $environments, 'prod');

        $this->assertEquals(['two' => $servers['two']], $stage->getHosts(null));
    }

    public function testByStageName()
    {
        $servers = new ServerCollection();
        $servers['one'] = new \stdClass();

        $environments = new EnvironmentCollection();
        $environments['one'] = $env = new Environment();
        $env->set('stages', ['prod']);

        $stage = new StageStrategy($servers, $environments);

        $this->assertEquals(['one' => $servers['one']], $stage->getHosts('prod'));
    }

    public function testByServerName()
    {
        $servers = new ServerCollection();
        $servers['one'] = new \stdClass();

        $environments = new EnvironmentCollection();
        $environments['one'] = $env = new Environment();

        $stage = new StageStrategy($servers, $environments);

        $this->assertEquals(['one' => $servers['one']], $stage->getHosts('one'));
    }
}

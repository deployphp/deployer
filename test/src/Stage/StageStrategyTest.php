<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Stage;

use Deployer\Server\Environment;
use Deployer\Server\EnvironmentCollection;
use Deployer\Server\ServerCollection;

class StageStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testDefault()
    {
        $servers = new ServerCollection();
        $environments = new EnvironmentCollection();

        $stage = new StageStrategy($servers, $environments);

        $this->assertArrayHasKey('localhost', $stage->getServers(null));
    }

    public function testWithoutStage()
    {
        $servers = new ServerCollection();
        $servers['one'] = new \stdClass();

        $environments = new EnvironmentCollection();
        $environments['one'] = new Environment();

        $stage = new StageStrategy($servers, $environments);

        $this->assertEquals(['one' => $servers['one']], $stage->getServers(null));
    }

    public function testByStageName()
    {
        $servers = new ServerCollection();
        $servers['one'] = new \stdClass();

        $environments = new EnvironmentCollection();
        $environments['one'] = $env = new Environment();
        $env->set('stages', ['prod']);

        $stage = new StageStrategy($servers, $environments);

        $this->assertEquals(['one' => $servers['one']], $stage->getServers('prod'));
    }

    public function testByServerName()
    {
        $servers = new ServerCollection();
        $servers['one'] = new \stdClass();

        $environments = new EnvironmentCollection();
        $environments['one'] = $env = new Environment();

        $stage = new StageStrategy($servers, $environments);

        $this->assertEquals(['one' => $servers['one']], $stage->getServers('one'));
    }
}

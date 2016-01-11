<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Stage;

use Deployer\Collection\Collection;
use Deployer\Server\Environment;
use Deployer\Server\EnvironmentCollection;
use Deployer\Server\ServerCollection;

class StageStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testDefault()
    {
        $servers = new ServerCollection();
        $environments = new EnvironmentCollection();
        $parameters = new Collection();

        $stage = new StageStrategy($servers, $environments, $parameters);

        $this->assertArrayHasKey('localhost', $stage->getServers(null), $parameters);
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

        $parameters = new Collection();

        $stage = new StageStrategy($servers, $environments, $parameters);

        $this->assertEquals(['one' => $servers['one']], $stage->getServers(null));
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

        $parameters = new Collection();
        $parameters->set(StageStrategy::PARAM_DEFAULT_STAGE, 'prod');

        $stage = new StageStrategy($servers, $environments, $parameters);

        $this->assertEquals(['two' => $servers['two']], $stage->getServers(null));
    }

    public function testByStageName()
    {
        $servers = new ServerCollection();
        $servers['one'] = new \stdClass();

        $environments = new EnvironmentCollection();
        $environments['one'] = $env = new Environment();
        $env->set('stages', ['prod']);

        $parameters = new Collection();

        $stage = new StageStrategy($servers, $environments, $parameters);

        $this->assertEquals(['one' => $servers['one']], $stage->getServers('prod'));
    }

    public function testByServerName()
    {
        $servers = new ServerCollection();
        $servers['one'] = new \stdClass();

        $environments = new EnvironmentCollection();
        $environments['one'] = $env = new Environment();

        $parameters = new Collection();

        $stage = new StageStrategy($servers, $environments, $parameters);

        $this->assertEquals(['one' => $servers['one']], $stage->getServers('one'));
    }
}

<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class FlowFrameworkTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/flow_framework.php';
    }

    public function testRecipe()
    {
        $this->assertEnvParameterEquals('flow_context', 'Production');
        $this->assertEnvParameterEquals('flow_command', 'flow');

        $expectedSharedDirs = [
            'Data/Persistent',
            'Data/Logs',
            'Configuration/{{flow_context}}'
        ];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $this->assertTaskIsDefined([
            'deploy:run_migrations',
            'deploy:publish_resources',
        ]);
        $this->assertGroupTaskStepsNumberEquals('deploy', 10);
    }
}

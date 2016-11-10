<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Stage;

use Deployer\Server\Environment;
use Deployer\Server\EnvironmentCollection;
use Deployer\Server\Local;
use Deployer\Server\ServerCollection;

class StageStrategy implements StageStrategyInterface
{
    /**
     * @var EnvironmentCollection
     */
    private $environments;

    /**
     * @var ServerCollection
     */
    private $servers;

    /**
     * @var ?string
     */
    private $defaultStage;

    public function __construct(ServerCollection $servers, EnvironmentCollection $environments, $defaultStage = null)
    {
        $this->servers = $servers;
        $this->environments = $environments;
        $this->defaultStage = $defaultStage;
    }

    /**
     * {@inheritdoc}
     */
    public function getServers($stage)
    {
        $servers = [];

        // Get a default stage (if any) if no stage given
        if (empty($stage)) {
            $stage = $this->getDefaultStage();
        }

        if (!empty($stage)) {

            // Look for servers which has in set `stages` current stage name.
            foreach ($this->environments as $name => $env) {
                // If server does not have any stage category, skip them
                if (in_array($stage, $env->get('stages', []), true)) {
                    $servers[$name] = $this->servers->get($name);
                }
            }

            // If still is empty, try to find server by name.
            if (empty($servers)) {
                if ($this->servers->has($stage)) {
                    $servers = [$stage => $this->servers->get($stage)];
                } else {
                    // Nothing found.
                    throw new \RuntimeException("Stage or server `$stage` was not found.");
                }
            }
        } else {
            // Otherwise run on all servers what does not specify stage.
            foreach ($this->environments as $name => $env) {
                if (!$env->has('stages')) {
                    $servers[$name] = $this->servers->get($name);
                }
            }
        }

        if (empty($servers)) {
            if (count($this->servers) === 0) {
                $local = new Local();
                $this->environments['localhost'] = new Environment();

                $servers = ['localhost' => $local];
            } else {
                throw new \RuntimeException('You need to specify at least one server or stage.');
            }
        }

        return $servers;
    }

    /**
     * Returns the default stage
     *
     * @return ?string
     */
    public function getDefaultStage()
    {
        return $this->defaultStage;
    }
}

<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Exception\Exception;

class HostSelector
{
    /**
     * @var HostCollection|Host[]
     */
    private $hosts;

    /**
     * @var string
     */
    private $defaultStage;

    public function __construct(HostCollection $hosts, $defaultStage = null)
    {
        $this->hosts = $hosts;
        $this->defaultStage = $defaultStage;
    }

    /**
     * @param string $stage
     * @return Host[]
     * @throws Exception
     */
    public function getHosts($stage)
    {
        $hosts = [];

        // Get a default stage if no stage given
        if (empty($stage)) {
            $stage = $this->defaultStage;
        }

        if (!empty($stage)) {
            // Look for hosts which has stage with current stage name
            foreach ($this->hosts as $host) {
                // If host does not have any stage, skip them
                if ($stage === $host->get('stage', false)) {
                    $hosts[$host->getHostname()] = $host;
                }
            }

            // If still is empty, try to find host by name
            if (empty($hosts)) {
                if ($this->hosts->has($stage)) {
                    $hosts = [$stage => $this->hosts->get($stage)];
                } else {
                    // Nothing found.
                    throw new Exception("Hostname or stage `$stage` was not found.");
                }
            }
        } else {
            // Otherwise run on all hosts what does not specify stage
            foreach ($this->hosts as $host) {
                if (!$host->has('stage')) {
                    $hosts[$host->getHostname()] = $host;
                }
            }
        }

        if (empty($hosts)) {
            if (count($this->hosts) === 0) {
                $hosts = ['localhost' => new Localhost()];
            } else {
                throw new Exception('You need to specify at least one host or stage.');
            }
        }

        return $hosts;
    }

    /**
     * @param $hostnames
     * @return Host[]
     */
    public function getByHostnames($hostnames)
    {
        $hostnames = Range::expand(array_map('trim', explode(',', $hostnames)));
        return array_map([$this->hosts, 'get'], $hostnames);
    }

    /**
     * @param array|string $roles
     * @return Host[]
     */
    public function getByRoles($roles)
    {
        if (is_string($roles)) {
            $roles = array_map('trim', explode(',', $roles));
        }

        $hosts = [];
        foreach ($this->hosts as $host) {
            foreach ($host->get('roles', []) as $role) {
                if (in_array($role, $roles, true)) {
                    $hosts[$host->getHostname()] = $host;
                }
            }
        }

        return $hosts;
    }
}

<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

class HostSelector
{
    private $hosts;

    public function __construct(HostCollection $hosts)
    {
        $this->hosts = $hosts;
    }

    /**
     * @param string $hosts
     * @param string $roles
     * @return Host[]
     */
    public function select($hosts, $roles)
    {
        if (!empty($hosts)) {
            return $this->getByHostnames($hosts);
        }
        if (!empty($roles)) {
            return $this->getByRoles($roles);
        }
        return $this->getAll();
    }

    /**
     * @return Host[]
     */
    public function getAll()
    {
        $hosts = [];
        foreach ($this->hosts as $host) {
            $hosts[] = $host;
        }
        return $hosts;
    }

    /**
     * @param string $hostnames
     * @return Host[]
     */
    public function getByHostnames(string $hostnames)
    {
        $hostnames = Range::expand(array_map('trim', explode(',', $hostnames)));
        return array_map([$this->hosts, 'get'], $hostnames);
    }

    /**
     * @param string $roles
     * @return Host[]
     */
    public function getByRoles(string $roles)
    {
        if (is_string($roles)) {
            $roles = array_map('trim', explode(',', $roles));
        }

        $hosts = [];
        foreach ($this->hosts as $host) {
            foreach ($host->get('roles', []) as $role) {
                if (in_array($role, $roles, true)) {
                    $hosts[] = $host;
                }
            }
        }

        return $hosts;
    }
}

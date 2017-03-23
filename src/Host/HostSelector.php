<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Exception\ConfigurationException;

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
            foreach ($this->hosts as $hostname => $host) {
                // If host does not have any stage, skip them
                if ($stage === $host->get('stage', false)) {
                    $hosts[$hostname] = $host;
                }
            }

            // If still is empty, try to find host by name
            if (empty($hosts)) {
                if ($this->hosts->has($stage)) {
                    $hosts = [$stage => $this->hosts->get($stage)];
                } else {
                    // Nothing found.
                    throw new ConfigurationException("Hostname or stage `$stage` was not found.");
                }
            }
        } else {
            // Otherwise run on all hosts what does not specify stage
            foreach ($this->hosts as $hostname => $host) {
                if (!$host->has('stage')) {
                    $hosts[$hostname] = $host;
                }
            }
        }

        if (empty($hosts)) {
            if (count($this->hosts) === 0) {
                $localhost = new Localhost();
                $hosts = ['localhost' => $localhost];
            } else {
                throw new ConfigurationException('You need to specify at least one host or stage.');
            }
        }

        return $hosts;
    }
}

<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Stage;

use Deployer\Collection\Collection;
use Deployer\Exception\ConfigurationException;
use Deployer\Host\Host;
use Deployer\Host\Localhost;

class StageStrategy implements StageStrategyInterface
{
    /**
     * @var Collection|Host[]
     */
    private $hosts;

    /**
     * @var string
     */
    private $defaultStage;

    public function __construct(Collection $hosts, $defaultStage = null)
    {
        $this->hosts = $hosts;
        $this->defaultStage = $defaultStage;
    }

    /**
     * {@inheritdoc}
     */
    public function getHosts($stage)
    {
        $hosts = [];

        // Get a default stage (if any) if no stage given
        if (empty($stage)) {
            $stage = $this->getDefaultStage();
        }

        if (!empty($stage)) {

            // Look for hosts which has in set `stages` current stage name.
            foreach ($this->hosts as $name => $host) {
                // If server does not have any stage category, skip them
                if (in_array($stage, $host->get('stages', []), true)) {
                    $hosts[$name] = $this->hosts->get($name);
                }
            }

            // If still is empty, try to find server by name.
            if (empty($hosts)) {
                if ($this->hosts->has($stage)) {
                    $hosts = [$stage => $this->hosts->get($stage)];
                } else {
                    // Nothing found.
                    throw new ConfigurationException("Stage or host `$stage` was not found.");
                }
            }
        } else {
            // Otherwise run on all servers what does not specify stage.
            foreach ($this->hosts as $name => $host) {
                if (!$host->has('stages')) {
                    $hosts[$name] = $this->hosts->get($name);
                }
            }
        }

        if (empty($hosts)) {
            if (count($this->hosts) === 0) {
                $localhost = new Localhost();

                $hosts = ['localhost' => $localhost];
            } else {
                throw new \RuntimeException('You need to specify at least one host or stage.');
            }
        }

        return $hosts;
    }

    /**
     * Returns the default stage
     *
     * @return string
     */
    public function getDefaultStage()
    {
        return $this->defaultStage;
    }
}

<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Host\Host;

trait CustomOption
{
    /**
     * @param Host[] $hosts
     * @param string[] $options
     */
    protected function applyOverrides(array $hosts, array $options)
    {
        $override = [];
        foreach ($options as $option) {
            list($name, $value) = explode('=', $option);
            $value = $this->castValueToPhpType(trim($value));
            $override[trim($name)] = $value;
        }

        foreach ($hosts as $host) {
            foreach ($override as $key => $value) {
                $host->set($key, $value);
            }
        }
    }

    /**
     * @param mixed $value
     * @return bool|mixed
     */
    protected function castValueToPhpType($value)
    {
        switch ($value) {
            case 'true':
                return true;
            case 'false':
                return false;
            default:
                return $value;
        }
    }
}

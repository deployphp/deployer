<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Collection\PersistentCollection;
use Deployer\Exception\Exception;
use function Deployer\on;
use function Deployer\Support\array_flatten;

class Storage
{
    /**
     * @param Host[] $hosts
     */
    public static function persist(array $hosts)
    {
        on($hosts, function (Host $host) {
            $values = [];

            // Materialize config values
            foreach ($host->getConfig()->getCollection() as $key => $value) {
                $values[$key] = $host->get($key);
            }

            $file = sys_get_temp_dir() . '/' . uniqid('deployer-') . '-' . $host->getHostname() . '.dep';
            $values['host_config_storage'] = $file;

            $persistentCollection = new PersistentCollection($file, $values);
            $persistentCollection->flush();

            $host->getConfig()->setCollection($persistentCollection);
        });
    }

    /**
     * @param Host[] $hosts
     * @throws Exception
     */
    public static function load(...$hosts)
    {
        $hosts = array_flatten($hosts);
        foreach ($hosts as $host) {
            $collection = $host->getConfig()->getCollection();

            if ($collection instanceof PersistentCollection) {
                $collection->load();
            } else {
                throw new Exception("Can't load data for `$host` host. Host doesn't persistent.");
            }
        }
    }

    /**
     * @param Host[] $hosts
     * @throws Exception
     */
    public static function flush(...$hosts)
    {
        $hosts = array_flatten($hosts);
        foreach ($hosts as $host) {
            $collection = $host->getConfig()->getCollection();

            if ($collection instanceof PersistentCollection) {
                $collection->flush();
            } else {
                throw new Exception("Can't load data for `$host` host. Host doesn't persistent.");
            }
        }
    }

    /**
     * @param Host $host
     * @param string $file
     */
    public static function setup(Host $host, string $file)
    {
        $persistentCollection = new PersistentCollection($file);
        $persistentCollection->load();
        $host->getConfig()->setCollection($persistentCollection);
    }
}

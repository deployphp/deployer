<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Collection\PersistentCollection;
use function Deployer\on;

class Storage
{
    public static function persist(Host ...$hosts)
    {
        on($hosts, function (Host $host) {
            $values = [];

            foreach ($host->getConfig()->getCollection() as $key => $value) {
                $values[$key] = $host->get($key);
            }

            $file = sys_get_temp_dir() . '/' . uniqid('deployer-') . '-' . $host->alias() . '.dep';
            $values['host_config_file'] = $file;

            $persistentCollection = new PersistentCollection($file, $values);
            $persistentCollection->flush();
            $host->getConfig()->setCollection($persistentCollection);
        });
    }

    public static function load(Host ...$hosts)
    {
        foreach ($hosts as $host) {
            $collection = $host->getConfig()->getCollection();
            if ($collection instanceof PersistentCollection) {
                $collection->load();
            } else {
                die("Can't load data for {$host->alias()} host. Host isn't persistent.");
            }
        }
    }

    public static function flush(Host ...$hosts)
    {
        foreach ($hosts as $host) {
            $collection = $host->getConfig()->getCollection();
            if ($collection instanceof PersistentCollection) {
                $collection->flush();
            } else {
                die("Can't load data for {$host->alias()} host. Host isn't persistent.");
            }
        }
    }

    public static function setup(Host $host, string $file)
    {
        $persistentCollection = new PersistentCollection($file);
        $persistentCollection->load();
        $host->getConfig()->setCollection($persistentCollection);
    }
}

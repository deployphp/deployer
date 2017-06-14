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
	 * In a multi user environment where multiple projects are located on the same instance(s)
	 *	if two or more users are deploying a project on the same environment, the generated hosts files
	 *	are overwritten or worse, the deploy will fail due to file permissions
	 *
	 * Make the persistent storage folder configurable or use the system tmp 
	 *	as default if not possible
	 * 
	 * @return string
	 * @throws Exception
	 */
	private static function _getPersistentStorageLocation() {
		$config = \Deployer\Deployer::get()->config;

		// default persistent storage in case we can't use the configured value
		// or we can't create the default location
		// use the system temporary folder and the current pid to make the path unique
		$tmp = sys_get_temp_dir() . '/' . uuid(posix_getpid());
		
		// get the location for the deployer configuration
		$deployerConfig = $config->has('deployer_config') ? $config->get('deployer_config') : null;

		if ( !is_null($deployerConfig) ) {
			// check if the folder exists and it's writable
			if ( !(is_dir($deployerConfig) && is_writable($deployerConfig)) ) {
				throw new Exception("Deployer folder `$deployerConfig` doesn't exists or doesn't writable.");
			}
		} else {
			// not configured, generate deployer folder name

			// use the home dir of the current user 
			// and the repository name
			$userInfo = posix_getpwuid(posix_getuid());
			
			// if for some reason we couldn't find a valid home folder
			// or if it's not writable, use the default location
			if ( !isset($userInfo['dir']) || !(is_dir($userInfo['dir']) && is_writable($userInfo['dir'])) ) {
				return $tmp;
			}
			
			// we have a folder name
			$deployerConfig = $userInfo['dir'] . '/.deployer';
			
			//if it doesn't exists, create it
			if ( !file_exists($deployerConfig) ) {
				mkdir($deployerConfig, 0777, true);
			} 

			//it exists, check if it's a folder and if it's writable
			if ( !(is_dir($deployerConfig) && is_writable($deployerConfig)) ) {
				return $tmp;
			}
		}
		
		// we will store the persistent data per repository
		$configRepository = $config->has('repository') ? $config->get('repository') : null;
		if (empty($configRepository)) {
			return $tmp;
		} 
		
		// we now have the repository name
		$repository = str_replace('/', '_', substr($configRepository, (strrpos($configRepository, ':') + 1)));
		
		return $deployerConfig . '/' . $repository;
	}

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

            $file = self::_getPersistentStorageLocation() . '/' . $host->getHostname() . '.dep';
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

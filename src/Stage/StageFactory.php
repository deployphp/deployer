<?php


namespace Deployer\Stage;


use Deployer\Deployer;

class StageFactory
{
    /**
     * @param $name
     * @param array $servers
     * @param bool $default
     * @return array
     */
    public static function create($name, array $servers, $default = false)
    {
        // Automatically turn on multistage support when creating a stage
        Deployer::$multistage = true;

        // Make the server list for this stage
        $servers = array_combine($servers, $servers);
        array_walk($servers, function(&$value, $name) {
            $value = Deployer::$servers[$name];
        });

        // Register the stage serverlist
        Deployer::$stages[$name] = $servers;

        // When defined as default, set the stage as default on Deployer
        if ( $default ) {
            Deployer::$defaultStage = $name;
        }

        return $servers;
    }
} 
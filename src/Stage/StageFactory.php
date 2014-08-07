<?php


namespace Deployer\Stage;


use Deployer\Deployer;

class StageFactory
{
    /**
     * @param $name
     * @param array $servers
     * @param bool $default
     * @return Stage
     * @throws \RuntimeException
     */
    public static function create($name, array $servers, array $options = array(), $default = false)
    {
        if ( count(Deployer::$servers) == 0 ) {
            throw new \RuntimeException('Server should be defined before you define any stages.');
        }

        // Automatically turn on multistage support when creating a stage
        Deployer::$multistage = true;

        // Make the server list for this stage
        $servers = array_combine($servers, $servers);
        array_walk($servers, function(&$value, $name) {
            if ( !isset(Deployer::$servers[$name]) ) {
                throw new \RuntimeException(sprintf('Server "%s" not found', $name));
            }
            $value = Deployer::$servers[$name];
        });

        // Register the stage serverlist
        Deployer::$stages[$name] = new Stage($name, $servers, $options);

        // When defined as default, set the stage as default on Deployer
        if ( $default ) {
            Deployer::$defaultStage = $name;
        }

        return Deployer::$stages[$name];
    }
} 
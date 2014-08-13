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
        $deployer = Deployer::get();

        if (count($deployer->getServers()) == 0) {
            throw new \RuntimeException('Server should be defined before you define any stages.');
        }

        // Automatically turn on multistage support when creating a stage
        $deployer->setMultistage(true);

        // Make the server list for this stage
        $servers = array_combine($servers, $servers);
        array_walk($servers, function (&$value, $name) use ($deployer) {
            $value = $deployer->getServer($name);
        });

        // Register the stage serverlist
        $stage = new Stage($name, $servers, $options);
        $deployer->addStage($name ,$stage);

        // When defined as default, set the stage as default on Deployer
        if ($default) {
            $deployer->setDefaultStage($name);
        }

        return $stage;
    }
} 
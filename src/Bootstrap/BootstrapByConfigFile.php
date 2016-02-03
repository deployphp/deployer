<?php
/**
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 *
 * Moved some initialization logic from src/functions.php to here, since
 * putting application logic in public functions which callable without
 * any restriction is not good.
 *
 * We do not need any inheritance or interface implementation here,
 * it's just simple POPO class.
 */

namespace Deployer\Bootstrap;

use Deployer\Type\DotArray;
use Deployer\Cluster\ClusterBuilder;
use Deployer\Server\Builder;
use Symfony\Component\Yaml\Yaml;

/**
 * @property string $configFile
 */
class BootstrapByConfigFile
{
    
    /**
     * @var string | null $configFile
     */
    public $configFile = null;

    /**
     * @var string | null $configFileContent
     */
    public $configFileContent = null;

    /**
     * @var array $clusterConfig
     */
    public $clusterConfig = [];

    /**
     * @var array $serverConfig
     */
    public $serverConfig = [];

    /**
     * @var Deployer\Cluster\ClusterBuilder[] $clusterBuilders
     */
    public $clusterBuilders = [];
    
    /**
     * @var Deployer\Server\Builder[] $serverBuilders
     */
    public $serverBuilders = [];



    /**
     * @param Deployer\Type\DotArray $config
     * @param Builder | ClusterBuilder $builder
     */
    private function executeBuilderMethods(DotArray $config, $builder)
    {
        if ($config->hasKey('identity_file')) {
            if ($config['identity_file'] === null) {
                $builder->identityFile();
            } else {
                $builder->identityFile(
                    $config['identity_file.public_key'],
                    $config['identity_file.private_key'],
                    $config['identity_file.password']
                );
            }

            unset($config['identity_file']);
        }

        if ($config->hasKey('identity_config')) {
            if ($config['identity_config'] === null) {
                $builder->configFile();
            } else {
                $builder->configFile($config['identity_config']);
            }
            unset($config['identity_config']);
        }

        if ($config->hasKey('forward_agent')) {
            $builder->forwardAgent();
            unset($config['forward_agent']);
        }

        foreach (['user', 'password', 'stage', 'pem_file'] as $key) {
            if ($config->hasKey($key)) {
                $method = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
                $builder->$method($config[$key]);
                unset($config[$key]);
            }
        }

        // Everything else are env vars.
        foreach ($config->toArray() as $key => $value) {
            $builder->env($key, $value);
        }
    }

    /**
     * @param array $serverList
     * @throws \RuntimeException
     * @return Deployer\Bootstrap\BootstrapByConfigFile
     */
    public function parseConfig()
    {
        try {
            $this->configFileContent = Yaml::parse(file_get_contents($this->configFile));
        } catch (\RuntimeException $e) {
            throw new \RuntimeException("Error in parsing " . $this->configFile . " file.");
        }

        foreach ($this->configFileContent as $key => $cnf) {
            if (isset($cnf['cluster']) && $cnf['cluster']) {
                $this->clusterConfig[$key] = $cnf;
            } else {
                $this->serverConfig[$key] = $cnf;
            }
        }

        return $this;
    }
    

    /**
     * @throws \RuntimeException
     * @return Deployer\Bootstrap\BootstrapByConfigFile
     */
    public function initServers()
    {
        foreach ((array) $this->serverConfig as $name => $config) {
            try {
                if (!is_array($config)) {
                    throw new \RuntimeException();
                }

                $da = new DotArray($config);

                if ($da->hasKey('local')) {
                    $builder = localServer($name);
                } else {
                    $builder = $da->hasKey('port') ?
                        $this->serverBuilders[] = server($name, $da['host'], $da['port']) :
                        $this->serverBuilders[] = server($name, $da['host']);
                }

                unset($da['local']);
                unset($da['host']);
                unset($da['port']);

                $this->executeBuilderMethods($da, $builder);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException("Error processing servers: ".$name);
            }
        }
        return $this;
    }


    /**
     * @throws \RuntimeException
     * @return Deployer\Bootstrap\BootstrapByConfigFile
     */
    public function initClusters()
    {
        foreach ((array) $this->clusterConfig as $name => $config) {
            try {
                $config = new DotArray($config);

                $clusterBuilder = $config->hasKey('port') ?
                    $this->clusterBuilders[] = cluster($name, $config['nodes'], $config['port']) :
                    $this->clusterBuilders[] = cluster($name, $config['nodes']);

                unset($config['local']);
                unset($config['nodes']);
                unset($config['port']);

                $this->executeBuilderMethods($config, $clusterBuilder);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException("Error processing clusters: ".$name);
            }
        }
        return $this;
    }

    /**
     * @param string $file
     */
    public function setConfig($file)
    {
        $this->configFile = $file;
        return $this;
    }
}

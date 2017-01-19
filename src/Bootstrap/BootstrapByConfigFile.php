<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Bootstrap;

use Deployer\Builder\BuilderInterface;
use Deployer\Collection\Collection;
use Symfony\Component\Yaml\Yaml;

/**
 * BootstrapByConfigFile
 *
 * Moved some initialization logic from src/functions.php to here, since
 * putting application logic in public functions which callable without
 * any restriction is not good.
 *
 * We do not need any inheritance or interface implementation here,
 * it's just simple POPO class.
 *
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 */
class BootstrapByConfigFile
{
    /**
     * @var string|null $configFile
     */
    public $configFile = null;

    /**
     * @var array|string|\stdClass|null $configFileContent
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
     * @var BuilderInterface[] $clusterBuilders
     */
    public $clusterBuilders = [];

    /**
     * @var BuilderInterface[] $serverBuilders
     */
    public $serverBuilders = [];

    /**
     * @param Collection $config
     * @param BuilderInterface $builder
     */
    private function executeBuilderMethods(Collection $config, BuilderInterface $builder)
    {
        if ($config->has('identity_file')) {
            if ($config['identity_file'] === null) {
                $builder->identityFile();
            } else {
                $builder->identityFile(
                    $config['identity_file']['public_key'],
                    $config['identity_file']['private_key'],
                    $config['identity_file']['password']
                );
            }

            unset($config['identity_file']);
        }

        if ($config->has('identity_config')) {
            if ($config['identity_config'] === null) {
                $builder->configFile();
            } else {
                $builder->configFile($config['identity_config']);
            }
            unset($config['identity_config']);
        }

        if ($config->has('forward_agent')) {
            $builder->forwardAgent();
            unset($config['forward_agent']);
        }

        foreach (['user', 'password', 'stage', 'pem_file'] as $key) {
            if ($config->has($key)) {
                $method = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
                $builder->$method($config[$key]);
                unset($config[$key]);
            }
        }

        // Everything else are set vars.
        foreach ($config->toArray() as $key => $value) {
            $builder->set($key, $value);
        }
    }

    /**
     * @throws \RuntimeException
     * @return \Deployer\Bootstrap\BootstrapByConfigFile
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
     * @return \Deployer\Bootstrap\BootstrapByConfigFile
     */
    public function initServers()
    {
        foreach ((array) $this->serverConfig as $name => $config) {
            try {
                if (!is_array($config)) {
                    throw new \RuntimeException();
                }

                $da = new Collection($config);

                if ($da->has('local')) {
                    $builder = \Deployer\localServer($name);
                } else {
                    $builder = $da->has('port') ?
                        $this->serverBuilders[] = \Deployer\server($name, $da['host'], $da['port']) :
                        $this->serverBuilders[] = \Deployer\server($name, $da['host']);
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
     * @return \Deployer\Bootstrap\BootstrapByConfigFile
     */
    public function initClusters()
    {
        foreach ((array) $this->clusterConfig as $name => $config) {
            try {
                $config = new Collection($config);

                $clusterBuilder = $config->has('port') ?
                    $this->clusterBuilders[] = \Deployer\cluster($name, $config['nodes'], $config['port']) :
                    $this->clusterBuilders[] = \Deployer\cluster($name, $config['nodes']);

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
     * @return \Deployer\Bootstrap\BootstrapByConfigFile
     */
    public function setConfig($file)
    {
        $this->configFile = $file;
        return $this;
    }
}

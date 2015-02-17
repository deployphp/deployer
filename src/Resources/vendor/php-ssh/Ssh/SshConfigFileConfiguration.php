<?php

namespace Ssh;

use RuntimeException;

/**
 * SSH Config File Configuration
 *
 * @author Cam Spiers <camspiers@gmail.com>
 */
class SshConfigFileConfiguration extends Configuration
{

    const DEFAULT_SSH_IDENTITY = '~/.ssh/id_rsa';

    protected $configs = array();
    protected $config;
    protected $match = array();

    /**
     * Constructor
     *
     * @param  string  $file
     * @param  string  $host
     * @param  integer $port
     * @param  array   $methods
     * @param  array   $callbacks
     * @param  string  $identity
     */
    public function __construct(
        $file, $host, $port = 22, array $methods = array(), array $callbacks = array(), $identity = null
    )
    {
        $this->parseSshConfigFile($this->processPath($file));
        $this->identity = is_null($identity) ? self::DEFAULT_SSH_IDENTITY : $identity;
        $this->config = $this->getConfigForHost($host);

        parent::__construct(
            isset($this->config['hostname']) ? $this->config['hostname'] : $host,
            isset($this->config['port']) ? $this->config['port'] : $port,
            $methods,
            $callbacks
        );
    }

    /**
     * Replaces '~'' with users home path
     * @param  string $path
     * @return string
     */
    protected function processPath($path)
    {
        return preg_replace('/^~/', getenv('HOME'), $path);
    }

    /**
     * Parses the ssh config file into an array of configs for later matching against hosts
     * @param  string $file
     */
    protected function parseSshConfigFile($file)
    {
        if (!file_exists($file) || !is_readable($file)) {
            throw new RuntimeException("The file '$file' does not exist or is not readable");
        }
        $contents = file_get_contents($file);
        $configs = array();
        $lineNumber = 1;
        foreach (explode(PHP_EOL, $contents) as $line) {
            $line = trim($line);
            if ($line == '' || $line[0] == '#') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos !== false) {
                $key = strtolower(trim(substr($line, 0, $pos)));
                $value = trim(substr($line, $pos + 1, strlen($line)));
            } else {
                $i = 0;
                while ($i < strlen($line)) {
                    if ($line[$i] == ' ') {
                        break;
                    }
                    $i++;
                }
                if ($i == strlen($line)) {
                    throw new RuntimeException("The file '$file' is not parsable at line '$lineNumber'");
                }
                $key = strtolower(trim(substr($line, 0, $i)));
                $value = trim(substr($line, $i + 1, strlen($line)));
            }
            if ($key == 'host') {
                $this->configs = array_merge($this->configs, $configs);
                $configs = array();
                $hosts = explode(' ', $value);
                foreach ($hosts as $host) {
                    $configs[] = array('host' => $host);
                }

            } else {
                foreach ($configs as $host => $config) {
                    $configs[$host][$key] = $value;
                }
            }
            $lineNumber++;
        }
        $this->configs = array_merge($this->configs, $configs);
    }

    /**
     * Merges matches of the host in the config file using fnmatch and a length comparison
     * @param  string $host
     * @return array
     */
    public function getConfigForHost($host)
    {
        $matches = array();
        foreach ($this->configs as $config) {
            if (fnmatch($config['host'], $host)) {
                $matches[] = $config;
            }
        }
        if (count($matches) == 0) {
            throw new RuntimeException("Unable to find configuration for host '{$host}'");
        }
        usort($matches, function ($a, $b) {
            return strlen($a['host']) > strlen($b['host']);
        });
        $result = array();
        foreach ($matches as $match) {
            $result = array_merge($result, $match);
        }
        unset($result['host']);
        if (isset($result['identityfile'])) {
            $result['identityfile'] = $this->processPath($result['identityfile']);
        } else if (file_exists($file = $this->processPath($this->getIdentity()))) {
            $result['identityfile'] = $file;
        }

        return $result;
    }

    /**
     * Return an authentication mechanism based on the configuration file
     * @param  string|null $passphrase
     * @param  string|null $user
     * @return PublicKeyFile|None
     */
    public function getAuthentication($passphrase = null, $user = null)
    {
        if (is_null($user) && !isset($this->config['user'])) {
            throw new RuntimeException("Can not authenticate for '{$this->host}' could not find user to authenticate as");
        }
        $user = $user ?: $this->config['user'];
        if (isset($this->config['identityfile'])) {
            return new Authentication\PublicKeyFile(
                $user,
                $this->config['identityfile'] . '.pub',
                $this->config['identityfile'],
                $passphrase
            );
        } else {
            return new Authentication\None(
                $user
            );
        }
    }
}
<?php


namespace Deployer\Stage;


use Deployer\Server\ServerInterface;

class Stage
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var ServerInterface[]
     */
    protected $servers = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param string $name Name of the stage
     * @param ServerInterface[] $servers List of servers
     * @param array $options List of additional options
     */
    public function __construct($name, array $servers, array $options = array())
    {
        $this->name = $name;
        $this->servers = $servers;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \Deployer\Server\ServerInterface[]
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * @param array $options
     */
    public function options(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value)
    {
        return $this->options[$key] = $value;
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    public function get($key, $default)
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
} 
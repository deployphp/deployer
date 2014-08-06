<?php


namespace Deployer\Stage;


class Stage
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $servers = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param string $name Name of the stage
     * @param array $servers List of servers
     * @param array $options List of additional options
     */
    public function __construct($name, array $servers, array $options = array())
    {
        $this->name = $name;
        $this->servers = $servers;
        $this->options = $options;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getServers()
    {
        return $this->servers;
    }

    public function options(array $options)
    {
        $this->options = $options;
    }

    public function set($key, $value)
    {
        return $this->options[$key] = $value;
    }

    public function get($key, $default)
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
    }

    public function getOptions()
    {
        return $this->options;
    }
} 
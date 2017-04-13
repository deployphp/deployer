<?php

namespace Deployer\Ssh;

/**
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class Arguments
{
    /**
     * @var array
     */
    private $flags = [];

    /**
     * @var array
     */
    private $options = [];

    public function getCliArguments() : string
    {
        $boolFlags  = array_keys(array_filter($this->flags, 'is_null'));

        $valueFlags = array_filter($this->flags);
        $valueFlags = array_map(function ($key, $value) {
            return sprintf('%s="%s"', $key, $value);
        }, array_keys($valueFlags), $valueFlags);

        $options    = array_map(function ($key, $value) {
            return sprintf('-o %s="%s"', $key, $value);
        }, array_keys($this->options), $this->options);

        return sprintf('%s %s %s', implode(' ', $boolFlags), implode(' ', $valueFlags), implode(' ', $options));
    }

    public function withFlags(array $flags) : Arguments
    {
        $clone = clone $this;
        $clone->flags = $flags;

        return $clone;
    }

    public function withOptions(array $options) : Arguments
    {
        $clone = clone $this;
        $clone->options = $options;

        return $clone;
    }

    public function withFlag(string $flag, string $value = null) : Arguments
    {
        $clone = clone $this;
        $clone->flags = array_merge($this->flags, [$flag => $value]);

        return $clone;
    }

    public function withOption(string $option, string $value) : Arguments
    {
        $clone = clone $this;
        $clone->options = array_merge($this->options, [$option => $value]);

        return $clone;
    }

    public function withDefaults(Arguments $defaultOptions) : Arguments
    {
        $clone = clone $this;
        $clone->flags = array_merge($defaultOptions->flags, $this->flags);
        $clone->options = array_merge($defaultOptions->options, $this->options);

        return $clone;
    }

    public function __toString() : string
    {
        return $this->getCliArguments();
    }
}

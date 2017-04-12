<?php

namespace Deployer\Ssh;

/**
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class Options
{
    /**
     * @var array
     */
    private $flags = [];

    /**
     * @var array
     */
    private $options = [];

    public function getOptionsString() : string
    {
        $flags = implode(' ', $this->flags);
        $options = implode(' ', array_map(function ($key, $value) {
            return sprintf('%s=%s', $key, $value);
        }, array_keys($this->options), $this->options));

        return sprintf('%s %s', $flags, $options);
    }

    public function withFlags(array $flags) : Options
    {
        $clone = clone $this;
        $clone->flags = $flags;

        return $clone;
    }

    public function withOptions(array $options) : Options
    {
        $clone = clone $this;
        $clone->options = $options;

        return $clone;
    }

    public function withFlag($flag) : Options
    {
        $clone = clone $this;
        $clone->flags = array_unique(array_merge($this->flags, [$flag]));

        return $clone;
    }

    public function withOption(string $option, string $value) : Options
    {
        $clone = clone $this;
        $clone->options = array_merge($this->options, [$option => $value]);

        return $clone;
    }

    public function withDefaults(Options $defaultOptions) : Options
    {
        $clone = clone $this;
        $clone->flags = array_merge($defaultOptions->flags, $this->flags);
        $clone->options = array_merge($defaultOptions->options, $this->options);

        return $clone;
    }

    public function __toString() : string
    {
        return $this->getOptionsString();
    }
}

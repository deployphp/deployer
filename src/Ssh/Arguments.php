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

        $args = sprintf('%s %s %s', implode(' ', $boolFlags), implode(' ', $valueFlags), implode(' ', $options));

        return trim(preg_replace('!\s+!', ' ', $args));
    }

    public function withFlags(array $flags) : Arguments
    {
        $clone = clone $this;
        $clone->flags = $this->buildFlagsFromArray($flags);

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
        $clone->options = array_merge($defaultOptions->options, $this->options);
        $clone->flags = array_merge($defaultOptions->flags, $this->flags);

        return $clone;
    }

    private function buildFlagsFromArray($flags) : array
    {
        $boolFlags = array_filter(array_map(function ($key, $value) {
            if (is_int($key)) {
                return $value;
            }

            if (null === $value) {
                return $key;
            }
        }, array_keys($flags), $flags));

        $valueFlags = array_filter($flags, function ($key, $value) {
            return is_string($key) && is_string($value);
        }, ARRAY_FILTER_USE_BOTH);

        return array_merge(array_fill_keys($boolFlags, null), $valueFlags);
    }

    public function __toString() : string
    {
        return $this->getCliArguments();
    }
}

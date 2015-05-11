<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Type;

/**
 * DotArray
 * Allow access $array['abc.xyz'] same $array['abc']['xyz']
 *
 * Thank Glynn Forrest with https://github.com/glynnforrest/Crutches
 *
 * @author OanhNN <oanhnn@rikkeisoft.com>
 * @version 1.0
 */
class DotArray implements \ArrayAccess
{

    /**
     * Storage array
     *
     * @var array
     */
    protected $array = [];

    /**
     * Validate key
     *
     * @param string $key
     * @return bool
     */
    public function validateKey($key)
    {
        return (bool) preg_match('/^(\w|\.)+$/', $key);
    }

    /**
     * Check has key
     *
     * @param string $key
     * @return bool
     * @throws \RuntimeException
     */
    public function hasKey($key)
    {
        if (!$this->validateKey($key)) {
            throw new \RuntimeException("Key `$key` is invalid");
        }

        $parts = explode('.', $key);
        $scope = &$this->array;
        $count = count($parts) - 1;
        for ($i = 0; $i < $count; $i++) {
            if (!isset($scope[$parts[$i]])) {
                return false;
            }
            $scope = &$scope[$parts[$i]];
        }
        
        return array_key_exists($parts[$i], $scope);
    }

    /**
     * Get all value as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->array;
    }

    /**
     * Check exist key . Like isset(), a value of null is considered not set.
     * isset($array['abc.xyz']) same isset($array['abc']['xyz'])
     *
     * @param string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return null !== $this->offsetGet($key);
    }

    /**
     * Get an array value
     * $array['abc.xyz'] same $array['abc']['xyz']
     *
     * @param string $key
     * @return mixed NULL will be returned if the key is not found.
     * @throws \RuntimeException
     */
    public function offsetGet($key)
    {
        if (!$this->validateKey($key)) {
            throw new \RuntimeException("Key `$key` is invalid");
        }

        $parts = explode('.', $key);
        $scope = &$this->array;
        $count = count($parts) - 1;
        for ($i = 0; $i < $count; $i++) {
            if (!isset($scope[$parts[$i]])) {
                return null;
            }
            $scope = &$scope[$parts[$i]];
        }
        
        return isset($scope[$parts[$i]]) ? $scope[$parts[$i]] : null;
    }

    /**
     * Set an array value
     * $array['abc.xyz'] = 'value' same $array['abc']['xyz'] = 'value'
     *
     * @param string $key
     * @param mixed $value
     * @throws \RuntimeException
     */
    public function offsetSet($key, $value)
    {
        if (!$this->validateKey($key)) {
            throw new \RuntimeException("Key `$key` is invalid");
        }

        $parts = explode('.', $key);
        //loop through each part, create it if not present.
        $scope = &$this->array;
        $count = count($parts) - 1;
        for ($i = 0; $i < $count; $i++) {
            if (!isset($scope[$parts[$i]])) {
                $scope[$parts[$i]] = [];
            }
            $scope = &$scope[$parts[$i]];
        }
        $scope[$parts[$i]] = $value;
    }

    /**
     * Unset an array value
     * unset($array['abc.xyz']) same unset($array['abc']['xyz'])
     * 
     * @param string $key
     */
    public function offsetUnset($key)
    {
        if (!$this->validateKey($key)) {
            throw new \RuntimeException("Key `$key` is invalid");
        }

        $parts = explode('.', $key);
        $scope = &$this->array;
        $count = count($parts) - 1;
        for ($i = 0; $i < $count; $i++) {
            if (!isset($scope[$parts[$i]])) {
                return;
            }
            $scope = &$scope[$parts[$i]];
        }
        unset($scope[$parts[$i]]);
    }

}

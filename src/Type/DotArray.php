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
     * @param array $array
     */
    public function __construct($array = [])
    {
        foreach ($array as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Validate key
     *
     * @param string $key
     * @return bool
     */
    public static function validateKey($key)
    {
        return (bool) preg_match('/^(\w|\.)+$/', $key);
    }

    /**
     * Explode key by separator character (a dot character)
     *
     * @param string $key
     * @return array
     * @throws \RuntimeException
     */
    protected function explodeKey($key)
    {
        if (!self::validateKey($key)) {
            throw new \RuntimeException("Key `$key` is invalid");
        }

        return explode('.', $key);
    }

    /**
     * Check has key
     *
     * @param string $key
     * @return bool
     */
    public function hasKey($key)
    {
        $parts = $this->explodeKey($key);
        $count = count($parts) - 1;
        $cKey = array_pop($parts);
        
        if (0 == $count) {
            $array = $this->array;
        } else {
            $pKey = implode('.', $parts);
            $array = $this->offsetGet($pKey);
        }
        
        return is_array($array) && array_key_exists($cKey, $array);
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
     */
    public function offsetGet($key)
    {
        $parts = $this->explodeKey($key);
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
     */
    public function offsetSet($key, $value)
    {
        $parts = $this->explodeKey($key);
        $scope = &$this->array;
        $count = count($parts) - 1;
        //loop through each part, create it if not present.
        for ($i = 0; $i < $count; $i++) {
            if (!isset($scope[$parts[$i]])) {
                $scope[$parts[$i]] = [];
            }
            $scope = &$scope[$parts[$i]];
        }
        if (is_array($value)) {
            $tmp = new static($value);
            $scope[$parts[$i]] = $tmp->toArray();
        } else {
            $scope[$parts[$i]] = $value;
        }
    }

    /**
     * Unset an array value
     * using unset($array['abc.xyz']) to unset($array['abc']['xyz'])
     * 
     * @param string $key
     */
    public function offsetUnset($key)
    {
        $parts = $this->explodeKey($key);
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

<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Password;

/**
 * Get password with use another function (Closure)
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class CallablePasswordGetter implements PasswordGetterInterface
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * Construct
     *
     * @param callable $callable
     */
    public function __construct($callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException(sprintf(
                'The first argument must be a callable, but "%s" given.',
                is_object($callable) ? get_class($callable) : gettype($callable)
            ));
        }

        $this->callable = $callable;
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword($host, $user)
    {
        return call_user_func($this->callable, $host, $user);
    }
}

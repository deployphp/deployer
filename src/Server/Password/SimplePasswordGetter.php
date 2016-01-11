<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Password;

/**
 * Simple password getter. Get password from constructs
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class SimplePasswordGetter implements PasswordGetterInterface
{
    /**
     * @var string
     */
    private $password;

    /**
     * Construct
     *
     * @param string $password
     */
    public function __construct($password)
    {
        $this->password = $password;
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword($host, $user)
    {
        return $this->password;
    }
}

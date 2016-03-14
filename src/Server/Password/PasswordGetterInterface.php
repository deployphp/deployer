<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Password;

/**
 * All password getter should implement this interface.
 * Get password from another system (Input, database or another system as example)
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
interface PasswordGetterInterface
{
    /**
     * Get password for connection
     *
     * @param string $host
     * @param string $user
     *
     * @return string
     */
    public function getPassword($host, $user);
}

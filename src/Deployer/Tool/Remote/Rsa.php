<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Tool\Remote;

class Rsa implements Key
{
    private $path;

    private $password;

    public function __construct($path, $password = null)
    {
        $this->password = $password;
        $this->path = $path;
    }

    public function key()
    {
        $key = new \Crypt_RSA();

        if (null !== $this->password) {
            $key->setPassword($this->password);
        }

        $key->loadKey(file_get_contents($this->getPath()));

        return $key;
    }

    /**
     * @param null $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return null
     */
    public function getPassword()
    {
        return $this->password;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        $path = $this->path;

        if (isset($_SERVER['HOME'])) {
            $path = str_replace('~', $_SERVER['HOME'], $path);
        }

        return $path;
    }


}
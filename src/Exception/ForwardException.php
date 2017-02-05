<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Exception;

class ForwardException extends Exception
{
    protected $serverName;
    protected $exceptionClass;
    protected $message;

    /**
     * ForwardException constructor.
     * @param $serverName
     * @param $exceptionClass
     * @param $message
     */
    public function __construct($serverName, $exceptionClass, $message)
    {
        $this->serverName = $serverName;
        $this->exceptionClass = $exceptionClass;
        parent::__construct($message);
    }

    public function __toString()
    {
        parent::__toString();
    }
}

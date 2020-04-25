<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Exception;

use Throwable;

class Exception extends \Exception
{
    private static $taskSourceLocation;
    private $taskFilename;
    private $taskLineNumber;

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if (function_exists('debug_backtrace')) {
            $trace = debug_backtrace();
            foreach ($trace as $t) {
                if (!empty($t['file']) && $t['file'] === self::$taskSourceLocation) {
                    $this->taskFilename = basename($t['file']);
                    $this->taskLineNumber = $t['line'];
                    break;
                }
            }
        }
        parent::__construct($message, $code, $previous);
    }

    public static function setTaskSourceLocation(string $filepath): void
    {
        self::$taskSourceLocation = $filepath;
    }

    public function getTaskFilename()
    {
        return $this->taskFilename;
    }

    public function getTaskLineNumber()
    {
        return $this->taskLineNumber;
    }
}


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
    /**
     * @var string
     */
    private static $taskSourceLocation = '';
    /**
     * @var string
     */
    private $taskFilename = '';
    /**
     * @var int|mixed
     */
    private $taskLineNumber = 0;

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
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

    public function getTaskFilename(): string
    {
        return $this->taskFilename;
    }

    public function getTaskLineNumber(): int
    {
        return $this->taskLineNumber;
    }

    public function setTaskFilename(string $taskFilename): void
    {
        $this->taskFilename = $taskFilename;
    }

    public function setTaskLineNumber(int $taskLineNumber): void
    {
        $this->taskLineNumber = $taskLineNumber;
    }
}


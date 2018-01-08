<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Logger\Handler;

class FileHandler implements HandlerInterface
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $buffer;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->buffer = '';

        // write remaining buffer to the log when php shuts down
        register_shutdown_function(function () {
            $this->flush();
        });
    }

    public function log(string $message)
    {
        $this->buffer .= $message;

        if (strlen($this->buffer) > 4096) {
            $this->flush();
        }
    }

    private function flush()
    {
        file_put_contents($this->filePath, $this->buffer, FILE_APPEND);
        $this->buffer = '';
    }
}

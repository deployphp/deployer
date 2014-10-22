<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Local;

class Process
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var array
     */
    private $descriptors;

    /**
     * @var array
     */
    private $pipes;

    /**
     * @var resource
     */
    private $process;

    /**
     * @var string
     */
    private $output;

    /**
     * @param string $command
     */
    public function __construct($command)
    {
        $this->command = $command;
        $this->descriptors = [
            0 => ["pipe", "r"], // stdin - read channel
            1 => ["pipe", "w"], // stdout - write channel
            2 => ["pipe", "w"], // stdout - error channel
            3 => ["pipe", "r"], // stdin
        ];
    }

    /**
     * Run command and get output.
     * @param string $command
     * @return string
     */
    public static function run($command)
    {
        $p = new self($command);
        $p->open();
        $output = $p->read();
        $p->close();

        return $output;
    }

    /**
     * Open process.
     */
    public function open()
    {
        $this->process = proc_open($this->command, $this->descriptors, $this->pipes);

        if (!is_resource($this->process)) {
            throw new \RuntimeException("Can't open resource with proc_open.");
        }
    }

    /**
     * Read from process i/o.
     * @return string
     */
    public function read()
    {
        $output = stream_get_contents($this->pipes[1]);

        $error = stream_get_contents($this->pipes[2]);

        if ($error) {
            throw new \RuntimeException($error);
        }

        return $output;
    }

    /**
     * @return int
     */
    public function close()
    {
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);
        fclose($this->pipes[3]);
        return proc_close($this->process); // Close all pipes before proc_close.
    }
}
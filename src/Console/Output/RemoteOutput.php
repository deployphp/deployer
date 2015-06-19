<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console\Output;

use Pure\Client;
use RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoteOutput implements OutputInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Client
     */
    private $pure;

    /**
     * @var string
     */
    private $server;

    /**
     * @param OutputInterface $output
     * @param Client $pure
     * @param string $server
     */
    public function __construct(OutputInterface $output, Client $pure, $server)
    {
        $this->output = $output;
        $this->pure = $pure;
        $this->server = $server;
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL)
    {
        $this->pure->queue('output')->push([$this->server, $messages, $newline, $type]);
    }

    /**
     * {@inheritdoc}
     */
    public function writeln($messages, $type = self::OUTPUT_NORMAL)
    {
        $this->write($messages, true, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function setVerbosity($level)
    {
        throw new RuntimeException('Can not modify verbosity in parallel mode.');
    }

    /**
     * {@inheritdoc}
     */
    public function getVerbosity()
    {
        return $this->output->getVerbosity();
    }

    /**
     * {@inheritdoc}
     */
    public function setDecorated($decorated)
    {
        throw new RuntimeException('Can not modify decorated in parallel mode.');
    }

    /**
     * {@inheritdoc}
     */
    public function isDecorated()
    {
        return $this->output->isDecorated();
    }

    /**
     * {@inheritdoc}
     */
    public function setFormatter(OutputFormatterInterface $formatter)
    {
        throw new RuntimeException('Can not modify formatter in parallel mode.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatter()
    {
        return $this->output->getFormatter();
    }
}

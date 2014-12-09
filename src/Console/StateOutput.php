<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;


use Symfony\Component\Console\Output\ConsoleOutput;

class StateOutput extends ConsoleOutput
{
    /**
     * @var bool
     */
    private $wasWritten = false;

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL)
    {
        $this->wasWritten = true;
        parent::write($messages, $newline, $type); 
    }

    /**
     * @param boolean $wasWritten
     */
    public function setWasWritten($wasWritten)
    {
        $this->wasWritten = $wasWritten;
    }

    /**
     * @return boolean
     */
    public function getWasWritten()
    {
        return $this->wasWritten;
    }
} 

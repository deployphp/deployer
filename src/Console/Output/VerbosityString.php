<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console\Output;

use Symfony\Component\Console\Output\OutputInterface;

class VerbosityString
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        switch ($this->output->getVerbosity()) {
            case OutputInterface::VERBOSITY_NORMAL:
                $verbosity = '';
                break;

            case OutputInterface::VERBOSITY_VERBOSE:
                $verbosity = '-v';
                break;

            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $verbosity = '-vv';
                break;

            case OutputInterface::VERBOSITY_DEBUG:
                $verbosity = '-vvv';
                break;

            case OutputInterface::VERBOSITY_QUIET:
                $verbosity = '-q';
                break;

            default:
                $verbosity = '';
        }

        return $verbosity;
    }
}

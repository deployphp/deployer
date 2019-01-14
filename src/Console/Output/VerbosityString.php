<?php declare(strict_types=1);
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

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function __toString(): string
    {
        switch ($this->output->getVerbosity()) {
            case OutputInterface::VERBOSITY_VERBOSE:
                return '-v';

            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                return '-vv';

            case OutputInterface::VERBOSITY_DEBUG:
                return '-vvv';

            case OutputInterface::VERBOSITY_QUIET:
                return '-q';

            case OutputInterface::VERBOSITY_NORMAL:
            default:
                return '';
        }
    }
}

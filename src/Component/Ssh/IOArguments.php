<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Component\Ssh;

use Deployer\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IOArguments
{
    public static function collect(InputInterface $input, OutputInterface $output): array
    {
        $arguments = [];
        foreach ($input->getOptions() as $name => $value) {
            if (!$input->getOption($name)) {
                continue;
            }
            if ($name === 'file') {
                $arguments[] = "--file";
                $arguments[] = ltrim($value, '=');
                continue;
            }
            if (in_array($name, ['verbose'], true)) {
                continue;
            }
            if (!is_array($value)) {
                $value = [$value];
            }
            foreach ($value as $v) {
                if (is_bool($v)) {
                    $arguments[] = "--$name";
                    continue;
                }

                $arguments[] = "--$name";
                $arguments[] = $v;
            }
        }

        if ($output->isDecorated()) {
            $arguments[] = '--decorated';
        }
        $verbosity = self::verbosity($output->getVerbosity());
        if (!empty($verbosity)) {
            $arguments[] = $verbosity;
        }
        return $arguments;
    }

    private static function verbosity(int $verbosity): string
    {
        switch ($verbosity) {
            case OutputInterface::VERBOSITY_QUIET:
                return '-q';
            case OutputInterface::VERBOSITY_NORMAL:
                return '';
            case OutputInterface::VERBOSITY_VERBOSE:
                return '-v';
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                return '-vv';
            case OutputInterface::VERBOSITY_DEBUG:
                return '-vvv';
            default:
                throw new Exception('Unknown verbosity level: ' . $verbosity);
        }
    }
}

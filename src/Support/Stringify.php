<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;


use Deployer\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Stringify
{
    public static function options(InputInterface $input, OutputInterface $output): string
    {
        $options = [];
        foreach ($input->getOptions() as $name => $option) {
            if (!$input->getOption($name)) {
                continue;
            }
            if ($name === 'file') {
                $options[] = "--file=" .self::escape(ltrim($option, '='));
                continue;
            }
            if (in_array($name, ['verbose'], true)) {
                continue;
            }
            if (!is_array($option)) {
                $option = [$option];
            }
            foreach ($option as $value) {
                $options[] = "--$name " . self::escape($value);
            }
        }

        if ($output->isDecorated()) {
            $options[] = '--decorated';
        }
        $verbosity = self::verbosity($output->getVerbosity());
        if (!empty($verbosity)) {
            $options[] = $verbosity;
        }
        return implode(' ', $options);
    }

    public static function verbosity(int $verbosity): string
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

    private static function escape(string $token): string
    {
        return preg_match('{^[\w-]+$}', $token) ? $token : escapeshellarg($token);
    }
}

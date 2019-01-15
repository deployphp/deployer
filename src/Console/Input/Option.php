<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console\Input;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class Option
{
    /**
     * @param InputInterface $input
     * @param InputOption    $option
     *
     * @return string
     */
    public static function toString(
        InputInterface $input,
        InputOption $option
    ): string {
        $name = $option->getName();

        if (!$option->acceptValue()) {
            return true === $input->getOption($name)
                ? \sprintf('--%s', $name)
                : '';
        }

        if (!$option->isArray()) {
            return self::generatePartialOption(
                $option,
                $name,
                $input->getOption($name)
            );
        }

        /** @var string[] $outputs */
        $outputs = [];
        foreach ($input->getOption($name) as $value) {
            $value = self::generatePartialOption(
                $option,
                $name,
                $value
            );

            if ($value === '') {
                continue;
            }

            $outputs[] = $value;
        }

        return \implode(' ', $outputs);
    }

    /**
     * @param InputOption $option
     * @param string      $name
     * @param null|string $value
     *
     * @return string
     */
    private static function generatePartialOption(
        InputOption $option,
        string $name,
        $value
    ): string {
        if (\null !== $value && \strlen($value) !== 0) {
            return \sprintf(
                '--%s=%s',
                $name,
                $value
            );
        }

        if ($option->isValueOptional()) {
            return \sprintf(
                '--%s',
                $name
            );
        }

        return '';
    }
}

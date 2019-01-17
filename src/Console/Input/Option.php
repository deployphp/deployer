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
    public static function toString(
        InputInterface $input,
        InputOption $option
    ): string {
        $name = $option->getName();
        $values = $input->getOption($name);

        if (!$option->acceptValue()) {
            return true === $values
                ? \sprintf('--%s', $name)
                : '';
        }

        if (!$option->isArray()) {
            $values = [$values];
        }

        $isValueRequired = $option->isValueRequired();
        /** @var string[] $outputs */
        $outputs = [];
        foreach ($values as $value) {
            if ($isValueRequired && \null === $value) {
                continue;
            }
            $value = sprintf(
                '--%s%s%s',
                $name,
                \null === $value ? '' : '=',
                $value
            );

            $outputs[] = $value;
        }

        return \implode(' ', $outputs);
    }
}

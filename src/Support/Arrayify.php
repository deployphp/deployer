<?php
declare(strict_types=1);

namespace Deployer\Support;

use Symfony\Component\Console\Input\InputInterface;

final class Arrayify
{
    public static function options(InputInterface $input): array
    {
        $options = array_filter($input->getOptions());

        return iterator_to_array(
            \iter\flatten(
                \iter\zip(
                    \iter\map(fn ($name) => "--$name", \iter\keys($options)),
                    \iter\values($options)
                )
            )
        );
    }
}

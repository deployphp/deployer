<?php declare(strict_types=1);
namespace Deployer\Support;

use Symfony\Component\Console\Input\InputInterface;
use function iter\flatten;
use function iter\map;
use function iter\mapWithKeys;
use function iter\toArray;

final class Arrayify
{
    public static function options(InputInterface $input): array
    {
        $options = array_filter($input->getOptions());

        return toArray(
            flatten(
                mapWithKeys(
                    fn ($v, $k) => is_array($v)
                        ? map(fn ($v) => ["--$k", $v], $v)
                        : ["--$k", $v],
                    $options
                )
            )
        );
    }
}

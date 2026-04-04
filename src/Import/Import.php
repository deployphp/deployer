<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Import;

use Deployer\Exception\Exception;

class Import
{
    /**
     * @param string|string[] $paths
     * @throws Exception
     */
    public static function import(mixed $paths): void
    {
        if (!is_array($paths)) {
            $paths = [$paths];
        }
        foreach ($paths as $path) {
            if (preg_match('/\.php$/i', $path)) {
                // Prevent variable leak into deploy.php file
                call_user_func(function () use ($path) {
                    // Reorder autoload stack
                    $originStack = spl_autoload_functions();

                    require $path;

                    $newStack = spl_autoload_functions();
                    if ($originStack[0] !== $newStack[0]) {
                        foreach (array_reverse($originStack) as $loader) {
                            spl_autoload_unregister($loader);
                            spl_autoload_register($loader, true, true);
                        }
                    }
                });
            } elseif (preg_match('/\.maml$/i', $path)) {
                $recipe = new MamlRecipe($path);
                $recipe->run();
            } elseif (preg_match('/\.ya?ml$/i', $path)) {
                YamlRecipe::exec($path);
            } else {
                throw new Exception("Unknown file format: $path");
            }
        }
    }
}

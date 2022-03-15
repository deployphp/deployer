<?php declare(strict_types=1);

namespace Deployer\Component\PharUpdate\Console;

use Deployer\Component\PharUpdate\Manager;
use Deployer\Component\PharUpdate\Manifest;
use Symfony\Component\Console\Helper\Helper as Base;

/**
 * The helper provides a Manager factory.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Helper extends Base
{
    /**
     * Returns the update manager.
     *
     * @param string $uri The manifest file URI.
     *
     * @return Manager The update manager.
     */
    public function getManager(string $uri): Manager
    {
        return new Manager(Manifest::loadFile($uri));
    }

    public function getName(): string
    {
        return 'phar-update';
    }
}

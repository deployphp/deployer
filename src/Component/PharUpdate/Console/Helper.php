<?php
namespace Deployer\Component\PharUpdate\Console;

use Deployer\Component\PharUpdate\Manifest;
use Deployer\Component\PharUpdate\Manager;
use Symfony\Component\Console\Helper\Helper as Base;

/**
 * The helper provides a Manager factory.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Helper extends Base
{
    /**
     * The update manager.
     *
     * @var Manager
     */
    private $manager;

    /**
     * Returns the update manager.
     *
     * @param string $uri The manifest file URI.
     *
     * @return Manager The update manager.
     */
    public function getManager($uri)
    {
        return new Manager(Manifest::loadFile($uri));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'phar-update';
    }
}
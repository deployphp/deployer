<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * Generate a composer deployer configuration
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ComposerTemplate extends FileResourceTemplate
{
    /**
     * {@inheritDoc}
     */
    protected function getFilePathOfResource()
    {
        return __DIR__ . '/../../Resources/templates/composer.php.dist';
    }
}

<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * All templates for initializer should implement this interface.
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
interface TemplateInterface
{
    /**
     * Initialize deployer
     *
     * @param string $filePath The file path for "deploy.php"
     * @param array $params
     */
    public function initialize($filePath, $params);
}

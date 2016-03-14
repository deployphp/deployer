<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

use Deployer\Initializer\Exception\ResourceNotFoundException;

/**
 * Abstract template for use file resource for create deployer configuration
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
abstract class FileResourceTemplate implements TemplateInterface
{
    /**
     * {@inheritDoc}
     */
    public function initialize($filePath)
    {
        $resourcePath = $this->getFilePathOfResource();

        if (!file_exists($resourcePath) || !is_file($resourcePath)) {
            throw new ResourceNotFoundException(sprintf(
                'Not found resource file "%s".',
                $resourcePath
            ));
        }

        if (!is_readable($resourcePath)) {
            throw new \RuntimeException(sprintf(
                'The resource file "%s" not readable.',
                $resourcePath
            ));
        }

        $resource = file_get_contents($resourcePath);

        $parameters = $this->getParametersForReplace();
        $replaceParameters = [];

        array_walk($parameters, function ($value, $key) use (&$replaceParameters) {
            $replaceParameters['%' . $key . '%'] = $value;
        });

        $resource = strtr($resource, $replaceParameters);

        file_put_contents($filePath, $resource);
    }

    /**
     * Get file path of resource
     *
     * @return string
     */
    abstract protected function getFilePathOfResource();

    /**
     * Get parameters for replace in resource
     *
     * @return array
     */
    protected function getParametersForReplace()
    {
        return [];
    }
}

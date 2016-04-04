<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * Abstract template for create deployer configuration.
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 * @author Anton Medvedev <anton@medv.io>
 */
abstract class Template implements TemplateInterface
{
    /**
     * {@inheritDoc}
     */
    public function initialize($filePath)
    {
        $content = $this->getTemplateContent();

        $parameters = $this->getParametersForReplace();
        $replaceParameters = [];
        array_walk($parameters, function ($value, $key) use (&$replaceParameters) {
            $replaceParameters['%' . $key . '%'] = $value;
        });
        $content = strtr($content, $replaceParameters);

        file_put_contents($filePath, $content);
    }

    /**
     * Get content of template.
     *
     * @return string
     */
    abstract protected function getTemplateContent();

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

<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Exception;

/**
 * Control template not found errors
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class TemplateNotFoundException extends \Exception
{
    /**
     * Create a new exception via template name
     *
     * @param string     $template
     * @param array      $availableTemplates
     * @param int        $code
     * @param \Exception $prev
     *
     * @return TemplateNotFoundException
     */
    public static function create($template, array $availableTemplates, $code = 0, \Exception $prev = null)
    {
        return new static(sprintf(
            'Not found template with name "%s". Available templates: "%s"',
            $template,
            implode('", "', $availableTemplates)
        ), $code, $prev);
    }
}

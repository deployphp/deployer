<?php declare(strict_types=1);
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
     * @param string[]      $availableTemplates
     */
    public static function create(string $template, array $availableTemplates, int $code = 0, \Throwable $prev = null): self
    {
        return new static(sprintf(
            'Not found template with name "%s". Available templates: "%s"',
            $template,
            implode('", "', $availableTemplates)
        ), $code, $prev);
    }
}

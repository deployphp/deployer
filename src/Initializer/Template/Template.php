<?php declare(strict_types=1);
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
    public function initialize(string $filePath, array $params)
    {
        $params = array_merge([
            'repository' => 'git@domain.com:username/repository.git',
            'allow_anonymous_stats' => true,
        ], $params);

        $content = $this->getTemplateContent($params);
        file_put_contents($filePath, $content);
    }

    /**
     * Get content of template.
     *
     * @param mixed[] $params
     */
    abstract protected function getTemplateContent(array $params): string;
}

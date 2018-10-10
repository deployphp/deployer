<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer;

use Deployer\Initializer\Exception\IOException;
use Deployer\Initializer\Exception\TemplateNotFoundException;
use Deployer\Initializer\Template\TemplateInterface;

/**
 * Initializer system
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class Initializer
{
    /**
     * @var array<string, TemplateInterface>
     */
    private $templates;

    /**
     * Add template to initializer
     */
    public function addTemplate(string $name, TemplateInterface $template): self
    {
        $this->templates[$name] = $template;

        return $this;
    }

    /**
     * Get template names
     *
     * @return string[]
     */
    public function getTemplateNames(): array
    {
        return array_keys($this->templates);
    }

    /**
     * Initialize deployer in project
     *
     * @param mixed[] $params
     *
     * @return string The configuration file path
     *
     * @throws IOException
     * @throws TemplateNotFoundException
     */
    public function initialize(
        string $templateName,
        string $directory,
        string $file = 'deploy.php',
        array $params = []
    ): string {
        $template = $this->getTemplateByName($templateName);

        $this->checkDirectoryBeforeInitialize($directory);
        $this->checkFileBeforeInitialize($directory, $file);

        $filePath = $directory . '/' . $file;

        $template->initialize($filePath, $params);

        return $filePath;
    }

    /**
     * @throws TemplateNotFoundException
     */
    private function getTemplateByName(string $templateName): TemplateInterface
    {
        if (!isset($this->templates[$templateName])) {
            throw TemplateNotFoundException::create($templateName, $this->getTemplateNames());
        }

        return $this->templates[$templateName];
    }

    /**
     * Check the directory before initialize
     *
     * @return void
     *
     * @throws IOException
     */
    private function checkDirectoryBeforeInitialize(string $directory)
    {
        if (!file_exists($directory)) {
            set_error_handler(function (int $errCode, string $errStr) use ($directory) {
                $parts = explode(':', $errStr, 2);
                $errorMessage = isset($parts[1]) && strlen(trim($parts[1])) !== 0
                    ? trim($parts[1])
                    : 'Undefined';

                throw new IOException(sprintf(
                    'Could not create directory "%s". %s',
                    $directory,
                    $errorMessage
                ), $errCode);
            });

            mkdir($directory, 0775);

            restore_error_handler();
        } elseif (!is_dir($directory)) {
            throw new IOException(sprintf(
                'Can not create directory. The path "%s" already exist.',
                $directory
            ));
        } elseif (!is_writable($directory)) {
            throw new IOException(sprintf(
                'The directory "%s" is not writable.',
                $directory
            ));
        }
    }

    /**
     * Check the file before initialize
     *
     * @return void
     *
     * @throws IOException
     */
    private function checkFileBeforeInitialize(string $directory, string $file)
    {
        $filePath = $directory . '/' . $file;

        if (file_exists($filePath)) {
            throw new IOException(sprintf(
                'The file "%s" already exist.',
                $filePath
            ));
        }

        touch($filePath);
    }
}

<?php declare(strict_types=1);

namespace Deployer\Component\PharUpdate;

use Deployer\Component\PharUpdate\Exception\InvalidArgumentException;
use Deployer\Component\PharUpdate\Version\Parser;
use Deployer\Component\PharUpdate\Version\Version;

/**
 * Manages the Phar update process.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Manager
{
    /**
     * The update manifest.
     *
     * @var Manifest
     */
    private $manifest;

    /**
     * The running file (the Phar that will be updated).
     *
     * @var string
     */
    private $runningFile;

    /**
     * Sets the update manifest.
     *
     * @param Manifest $manifest The manifest.
     */
    public function __construct(Manifest $manifest)
    {
        $this->manifest = $manifest;
    }

    /**
     * Returns the manifest.
     *
     * @return Manifest The manifest.
     */
    public function getManifest(): Manifest
    {
        return $this->manifest;
    }

    /**
     * Returns the running file (the Phar that will be updated).
     *
     * @return string The file.
     */
    public function getRunningFile(): string
    {
        if (null === $this->runningFile) {
            $this->runningFile = realpath($_SERVER['argv'][0]);
        }

        return $this->runningFile;
    }

    /**
     * Sets the running file (the Phar that will be updated).
     *
     * @param string $file The file name or path.
     *
     * @throws Exception\Exception
     * @throws InvalidArgumentException If the file path is invalid.
     */
    public function setRunningFile(string $file): void
    {
        if (false === is_file($file)) {
            throw InvalidArgumentException::create(
                'The file "%s" is not a file or it does not exist.',
                $file
            );
        }

        $this->runningFile = $file;
    }

    /**
     * Updates the running Phar if any is available.
     *
     * @param string|Version $version  The current version.
     * @param boolean        $major    Lock to current major version?
     * @param boolean        $pre      Allow pre-releases?
     *
     * @return boolean TRUE if an update was performed, FALSE if none available.
     */
    public function update($version, bool $major = false, bool $pre = false): bool
    {
        if (false === ($version instanceof Version)) {
            $version = Parser::toVersion($version);
        }

        if (null !== ($update = $this->manifest->findRecent(
            $version,
            $major,
            $pre
        ))) {
            $update->getFile();
            $update->copyTo($this->getRunningFile());

            return true;
        }

        return false;
    }
}

<?php declare(strict_types=1);

namespace Deployer\Component\PharUpdate\Version;

/**
 * Stores and returns the version information.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Version
{
    /**
     * The build metadata identifiers.
     *
     * @var array
     */
    protected $build;

    /**
     * The major version number.
     *
     * @var integer
     */
    protected $major;

    /**
     * The minor version number.
     *
     * @var integer
     */
    protected $minor;

    /**
     * The patch version number.
     *
     * @var integer
     */
    protected $patch;

    /**
     * The pre-release version identifiers.
     *
     * @var array
     */
    protected $preRelease;

    /**
     * Sets the version information.
     *
     * @param int $major The major version number.
     * @param int $minor The minor version number.
     * @param int $patch The patch version number.
     * @param array   $pre   The pre-release version identifiers.
     * @param array   $build The build metadata identifiers.
     */
    public function __construct(
        int $major = 0,
        int $minor = 0,
        int $patch = 0,
        array $pre = array(),
        array $build = array()
    ) {
        $this->build = $build;
        $this->major = $major;
        $this->minor = $minor;
        $this->patch = $patch;
        $this->preRelease = $pre;
    }

    /**
     * Returns the build metadata identifiers.
     *
     * @return array The build metadata identifiers.
     */
    public function getBuild(): array
    {
        return $this->build;
    }

    /**
     * Returns the major version number.
     *
     * @return int The major version number.
     */
    public function getMajor(): int
    {
        return $this->major;
    }

    /**
     * Returns the minor version number.
     *
     * @return int The minor version number.
     */
    public function getMinor(): int
    {
        return $this->minor;
    }

    /**
     * Returns the patch version number.
     *
     * @return int The patch version number.
     */
    public function getPatch(): int
    {
        return $this->patch;
    }

    /**
     * Returns the pre-release version identifiers.
     *
     * @return array The pre-release version identifiers.
     */
    public function getPreRelease(): array
    {
        return $this->preRelease;
    }

    /**
     * Checks if the version number is stable.
     *
     * @return boolean TRUE if it is stable, FALSE if not.
     */
    public function isStable(): bool
    {
        return empty($this->preRelease) && $this->major !== 0;
    }

    /**
     * Returns string representation.
     *
     * @return string The string representation.
     */
    public function __toString(): string
    {
        return Dumper::toString($this);
    }
}

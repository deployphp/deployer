<?php

namespace Deployer\Component\Version;

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
     * @param integer $major The major version number.
     * @param integer $minor The minor version number.
     * @param integer $patch The patch version number.
     * @param array   $pre   The pre-release version identifiers.
     * @param array   $build The build metadata identifiers.
     */
    public function __construct(
        $major = 0,
        $minor = 0,
        $patch = 0,
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
    public function getBuild()
    {
        return $this->build;
    }

    /**
     * Returns the major version number.
     *
     * @return integer The major version number.
     */
    public function getMajor()
    {
        return $this->major;
    }

    /**
     * Returns the minor version number.
     *
     * @return integer The minor version number.
     */
    public function getMinor()
    {
        return $this->minor;
    }

    /**
     * Returns the patch version number.
     *
     * @return integer The patch version number.
     */
    public function getPatch()
    {
        return $this->patch;
    }

    /**
     * Returns the pre-release version identifiers.
     *
     * @return array The pre-release version identifiers.
     */
    public function getPreRelease()
    {
        return $this->preRelease;
    }

    /**
     * Checks if the version number is stable.
     *
     * @return boolean TRUE if it is stable, FALSE if not.
     */
    public function isStable()
    {
        return empty($this->preRelease) && $this->major !== 0;
    }

    /**
     * Returns string representation.
     *
     * @return string The string representation.
     */
    public function __toString()
    {
        return Dumper::toString($this);
    }
}

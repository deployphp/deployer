<?php

namespace Deployer\Component\Version;

use Deployer\Component\Version\Exception\InvalidIdentifierException;
use Deployer\Component\Version\Exception\InvalidNumberException;

/**
 * Builds a new version number.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Builder extends Version
{
    /**
     * Removes the build metadata identifiers.
     *
     * @return Builder The Version builder.
     */
    public function clearBuild()
    {
        return $this->build = array();
    }

    /**
     * Removes the pre-release version identifiers.
     *
     * @return Builder The Version builder.
     */
    public function clearPreRelease()
    {
        $this->preRelease = array();
    }

    /**
     * Creates a new Version builder.
     *
     * @return Builder The Version builder.
     */
    public static function create()
    {
        return new Builder();
    }

    /**
     * Returns a readonly Version instance.
     *
     * @return Version The readonly Version instance.
     */
    public function getVersion()
    {
        return new Version(
            $this->major,
            $this->minor,
            $this->patch,
            $this->preRelease,
            $this->build
        );
    }

    /**
     * Imports the version components.
     *
     * @param array $components The components.
     *
     * @return Builder The Version builder.
     */
    public function importComponents(array $components)
    {
        if (isset($components[Parser::BUILD])) {
            $this->build = $components[Parser::BUILD];
        } else {
            $this->build = array();
        }

        if (isset($components[Parser::MAJOR])) {
            $this->major = $components[Parser::MAJOR];
        } else {
            $this->major = 0;
        }

        if (isset($components[Parser::MINOR])) {
            $this->minor = $components[Parser::MINOR];
        } else {
            $this->minor = 0;
        }

        if (isset($components[Parser::PATCH])) {
            $this->patch = $components[Parser::PATCH];
        } else {
            $this->patch = 0;
        }

        if (isset($components[Parser::PRE_RELEASE])) {
            $this->preRelease = $components[Parser::PRE_RELEASE];
        } else {
            $this->preRelease = array();
        }

        return $this;
    }

    /**
     * Imports the version string representation.
     *
     * @param string $version The string representation.
     *
     * @return Builder The Version builder.
     */
    public function importString($version)
    {
        return $this->importComponents(Parser::toComponents($version));
    }

    /**
     * Imports an existing Version instance.
     *
     * @param Version $version A Version instance.
     *
     * @return Builder The Version builder.
     */
    public function importVersion($version)
    {
        return $this
            ->setMajor($version->getMajor())
            ->setMinor($version->getMinor())
            ->setPatch($version->getPatch())
            ->setPreRelease($version->getPreRelease())
            ->setBuild($version->getBuild());
    }

    /**
     * Increments the major version number and resets the minor and patch
     * version numbers to zero.
     *
     * @param integer $amount Increment by what amount?
     *
     * @return Builder The Version builder.
     */
    public function incrementMajor($amount = 1)
    {
        $this->major += $amount;
        $this->minor = 0;
        $this->patch = 0;

        return $this;
    }

    /**
     * Increments the minor version number and resets the patch version number
     * to zero.
     *
     * @param integer $amount Increment by what amount?
     *
     * @return Builder The Version builder.
     */
    public function incrementMinor($amount = 1)
    {
        $this->minor += $amount;
        $this->patch = 0;

        return $this;
    }

    /**
     * Increments the patch version number.
     *
     * @param integer $amount Increment by what amount?
     *
     * @return Builder The Version builder.
     */
    public function incrementPatch($amount = 1)
    {
        $this->patch += $amount;

        return $this;
    }

    /**
     * Sets the build metadata identifiers.
     *
     * @param array $identifiers The build metadata identifiers.
     *
     * @return Builder The Version builder.
     *
     * @throws InvalidIdentifierException If an identifier is invalid.
     */
    public function setBuild(array $identifiers)
    {
        foreach ($identifiers as $identifier) {
            if (!Validator::isIdentifier($identifier)) {
                throw new InvalidIdentifierException($identifier);
            }
        }

        $this->build = $identifiers;

        return $this;
    }

    /**
     * Sets the major version number.
     *
     * @param integer $number The major version number.
     *
     * @return Builder The Version builder.
     *
     * @throws InvalidNumberException If the number is invalid.
     */
    public function setMajor($number)
    {
        if (!Validator::isNumber($number)) {
            throw new InvalidNumberException($number);
        }

        $this->major = intval($number);

        return $this;
    }

    /**
     * Sets the minor version number.
     *
     * @param integer $number The minor version number.
     *
     * @return Builder The Version builder.
     *
     * @throws InvalidNumberException If the number is invalid.
     */
    public function setMinor($number)
    {
        if (!Validator::isNumber($number)) {
            throw new InvalidNumberException($number);
        }

        $this->minor = intval($number);

        return $this;
    }

    /**
     * Sets the patch version number.
     *
     * @param integer $number The patch version number.
     *
     * @return Builder The Version builder.
     *
     * @throws InvalidNumberException If the number is invalid.
     */
    public function setPatch($number)
    {
        if (!Validator::isNumber($number)) {
            throw new InvalidNumberException($number);
        }

        $this->patch = intval($number);

        return $this;
    }

    /**
     * Sets the pre-release version identifiers.
     *
     * @param array $identifiers The pre-release version identifiers.
     *
     * @return Builder The Version builder.
     *
     * @throws InvalidIdentifierException If an identifier is invalid.
     */
    public function setPreRelease(array $identifiers)
    {
        foreach ($identifiers as $identifier) {
            if (!Validator::isIdentifier($identifier)) {
                throw new InvalidIdentifierException($identifier);
            }
        }

        $this->preRelease = $identifiers;

        return $this;
    }
}

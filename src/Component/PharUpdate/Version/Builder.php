<?php declare(strict_types=1);

namespace Deployer\Component\PharUpdate\Version;

use Deployer\Component\PharUpdate\Version\Exception\InvalidIdentifierException;
use Deployer\Component\PharUpdate\Version\Exception\InvalidNumberException;

/**
 * Builds a new version number.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Builder extends Version
{
    /**
     * Removes the build metadata identifiers.
     */
    public function clearBuild(): void
    {
        $this->build = array();
    }

    /**
     * Removes the pre-release version identifiers.
     */
    public function clearPreRelease(): void
    {
        $this->preRelease = array();
    }

    /**
     * Creates a new Version builder.
     *
     * @return Builder The Version builder.
     */
    public static function create(): Builder
    {
        return new Builder();
    }

    /**
     * Returns a readonly Version instance.
     *
     * @return Version The readonly Version instance.
     */
    public function getVersion(): Version
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
    public function importComponents(array $components): self
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
    public function importString(string $version): self
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
    public function importVersion(Version $version): self
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
     * @param int $amount Increment by what amount?
     *
     * @return Builder The Version builder.
     */
    public function incrementMajor(int $amount = 1): self
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
     * @param int $amount Increment by what amount?
     *
     * @return Builder The Version builder.
     */
    public function incrementMinor(int $amount = 1): self
    {
        $this->minor += $amount;
        $this->patch = 0;

        return $this;
    }

    /**
     * Increments the patch version number.
     *
     * @param int $amount Increment by what amount?
     *
     * @return Builder The Version builder.
     */
    public function incrementPatch(int $amount = 1): self
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
    public function setBuild(array $identifiers): self
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
     * @param int $number The major version number.
     *
     * @return Builder The Version builder.
     *
     * @throws InvalidNumberException If the number is invalid.
     */
    public function setMajor(int $number): self
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
     * @param int $number The minor version number.
     *
     * @return Builder The Version builder.
     *
     * @throws InvalidNumberException If the number is invalid.
     */
    public function setMinor(int $number): self
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
     * @param int $number The patch version number.
     *
     * @return Builder The Version builder.
     *
     * @throws InvalidNumberException If the number is invalid.
     */
    public function setPatch(int $number): self
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
    public function setPreRelease(array $identifiers): self
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

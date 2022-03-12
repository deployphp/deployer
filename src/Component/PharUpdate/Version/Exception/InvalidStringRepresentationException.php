<?php declare(strict_types=1);

namespace Deployer\Component\PharUpdate\Version\Exception;

/**
 * Throw if an invalid version string representation is used.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class InvalidStringRepresentationException extends VersionException
{
    /**
     * The invalid string representation.
     *
     * @var string
     */
    private $version;

    /**
     * Sets the invalid string representation.
     *
     * @param string $version The string representation.
     */
    public function __construct(string $version)
    {
        parent::__construct(
            sprintf(
                'The version string representation "%s" is invalid.',
                $version
            )
        );

        $this->version = $version;
    }

    /**
     * Returns the invalid string representation.
     *
     * @return string The invalid string representation.
     */
    public function getVersion(): string
    {
        return $this->version;
    }
}

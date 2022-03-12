<?php declare(strict_types=1);

namespace Deployer\Component\PharUpdate\Version\Exception;

/**
 * Thrown if an invalid identifier is used.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class InvalidIdentifierException extends VersionException
{
    /**
     * The invalid identifier.
     *
     * @var string
     */
    private $identifier;

    /**
     * Sets the invalid identifier.
     *
     * @param string $identifier The invalid identifier.
     */
    public function __construct(string $identifier)
    {
        parent::__construct(
            sprintf(
                'The identifier "%s" is invalid.',
                $identifier
            )
        );

        $this->identifier = $identifier;
    }

    /**
     * Returns the invalid identifier.
     *
     * @return string The invalid identifier.
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}

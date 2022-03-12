<?php declare(strict_types=1);

namespace Deployer\Component\PharUpdate\Version\Exception;

/**
 * Thrown if an invalid version number is used.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class InvalidNumberException extends VersionException
{
    /**
     * The invalid version number.
     *
     * @var mixed
     */
    private $number;

    /**
     * Sets the invalid version number.
     *
     * @param mixed $number The invalid version number.
     */
    public function __construct($number)
    {
        parent::__construct(
            sprintf(
                'The version number "%s" is invalid.',
                $number
            )
        );

        $this->number = $number;
    }

    /**
     * Returns the invalid version number.
     *
     * @return mixed The invalid version number.
     */
    public function getNumber()
    {
        return $this->number;
    }
}

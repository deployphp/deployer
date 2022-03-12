<?php declare(strict_types=1);

namespace Deployer\Component\PharUpdate\Version;

/**
 * Validates version information.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Validator
{
    /**
     * The regular expression for a valid identifier.
     */
    const IDENTIFIER_REGEX = '/^[0-9A-Za-z\-]+$/';

    /**
     * The regular expression for a valid semantic version number.
     */
    const VERSION_REGEX = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/';

    /**
     * Checks if a identifier is valid.
     *
     * @param string $identifier A identifier.
     *
     * @return boolean TRUE if the identifier is valid, FALSE If not.
     */
    public static function isIdentifier(string $identifier): bool
    {
        return (true == preg_match(self::IDENTIFIER_REGEX, $identifier));
    }

    /**
     * Checks if a number is a valid version number.
     *
     * @param integer $number A number.
     *
     * @return boolean TRUE if the number is valid, FALSE If not.
     */
    public static function isNumber(int $number): bool
    {
        return (true == preg_match('/^(0|[1-9]\d*)$/', (string)$number));
    }

    /**
     * Checks if the string representation of a version number is valid.
     *
     * @param string $version The string representation.
     *
     * @return boolean TRUE if the string representation is valid, FALSE if not.
     */
    public static function isVersion(string $version): bool
    {
        return (true == preg_match(self::VERSION_REGEX, $version));
    }
}

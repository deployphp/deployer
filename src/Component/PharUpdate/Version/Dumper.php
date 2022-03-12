<?php declare(strict_types=1);

namespace Deployer\Component\PharUpdate\Version;

/**
 * Dumps the Version instance to a variety of formats.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Dumper
{
    /**
     * Returns the components of a Version instance.
     *
     * @param Version $version A version.
     *
     * @return array The components.
     */
    public static function toComponents(Version $version)
    {
        return array(
            Parser::MAJOR => $version->getMajor(),
            Parser::MINOR => $version->getMinor(),
            Parser::PATCH => $version->getPatch(),
            Parser::PRE_RELEASE => $version->getPreRelease(),
            Parser::BUILD => $version->getBuild()
        );
    }

    /**
     * Returns the string representation of a Version instance.
     *
     * @param Version $version A version.
     *
     * @return string The string representation.
     */
    public static function toString(Version $version)
    {
        return sprintf(
            '%d.%d.%d%s%s',
            $version->getMajor(),
            $version->getMinor(),
            $version->getPatch(),
            $version->getPreRelease()
                ? '-' . join('.', $version->getPreRelease())
                : '',
            $version->getBuild()
                ? '+' . join('.', $version->getBuild())
                : ''
        );
    }
}

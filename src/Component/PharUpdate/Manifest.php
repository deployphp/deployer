<?php

namespace Deployer\Component\PharUpdate;

use Deployer\Component\Version\Comparator;
use Deployer\Component\Version\Parser;
use Deployer\Component\Version\Version;

/**
 * Manages the contents of an updates manifest file.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Manifest
{
    /**
     * The list of updates in the manifest.
     *
     * @var Update[]
     */
    private $updates;

    /**
     * Sets the list of updates from the manifest.
     *
     * @param Update[] $updates The updates.
     */
    public function __construct(array $updates = array())
    {
        $this->updates = $updates;
    }

    /**
     * Finds the most recent update and returns it.
     *
     * @param Version $version The current version.
     * @param boolean $major   Lock to major version?
     * @param boolean $pre     Allow pre-releases?
     *
     * @return Update The update.
     */
    public function findRecent(Version $version, $major = false, $pre = false)
    {
        /** @var $current Update */
        $current = null;
        $major = $major ? $version->getMajor() : null;

        foreach ($this->updates as $update) {
            if ($major && ($major !== $update->getVersion()->getMajor())) {
                continue;
            }

            if ((false === $pre)
                && !$update->getVersion()->isStable()) {
                continue;
            }

            $test = $current ? $current->getVersion() : $version;

            if (false === $update->isNewer($test)) {
                continue;
            }

            $current = $update;
        }

        return $current;
    }

    /**
     * Returns the list of updates in the manifest.
     *
     * @return Update[] The updates.
     */
    public function getUpdates()
    {
        return $this->updates;
    }

    /**
     * Loads the manifest from a JSON encoded string.
     *
     * @param string $json The JSON encoded string.
     *
     * @return Manifest The manifest.
     */
    public static function load($json)
    {
        return self::create(json_decode($json));
    }

    /**
     * Loads the manifest from a JSON encoded file.
     *
     * @param string $file The JSON encoded file.
     *
     * @return Manifest The manifest.
     */
    public static function loadFile($file)
    {
        return self::create(json_decode(file_get_contents($file)));
    }

    /**
     * Validates the data, processes it, and returns a new instance of Manifest.
     *
     * @param array $decoded The decoded JSON data.
     *
     * @return Manifest The new instance.
     */
    private static function create($decoded)
    {
        $updates = array();

        foreach ($decoded as $update) {
            $updates[] = new Update(
                $update->name,
                $update->sha1,
                $update->url,
                Parser::toVersion($update->version),
                isset($update->publicKey) ? $update->publicKey : null
            );
        }

        usort(
            $updates,
            function (Update $a, Update $b) {
                return Comparator::isGreaterThan(
                    $a->getVersion(),
                    $b->getVersion()
                );
            }
        );

        return new static($updates);
    }
}

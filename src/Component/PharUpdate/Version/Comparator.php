<?php declare(strict_types=1);

namespace Deployer\Component\PharUpdate\Version;

/**
 * Compares two Version instances.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Comparator
{
    /**
     * The version is equal to another.
     */
    const EQUAL_TO = 0;

    /**
     * The version is greater than another.
     */
    const GREATER_THAN = 1;

    /**
     * The version is less than another.
     */
    const LESS_THAN = -1;

    /**
     * Compares one version with another.
     *
     * @param Version $left  The left version to compare.
     * @param Version $right The right version to compare.
     *
     * @return integer Returns Comparator::EQUAL_TO if the two versions are
     *                 equal. If the left version is less than the right
     *                 version, Comparator::LESS_THAN is returned. If the left
     *                 version is greater than the right version,
     *                 Comparator::GREATER_THAN is returned.
     */
    public static function compareTo(Version $left, Version $right)
    {
        switch (true) {
            case ($left->getMajor() < $right->getMajor()):
                return self::LESS_THAN;
            case ($left->getMajor() > $right->getMajor()):
                return self::GREATER_THAN;
            case ($left->getMinor() > $right->getMinor()):
                return self::GREATER_THAN;
            case ($left->getMinor() < $right->getMinor()):
                return self::LESS_THAN;
            case ($left->getPatch() > $right->getPatch()):
                return self::GREATER_THAN;
            case ($left->getPatch() < $right->getPatch()):
                return self::LESS_THAN;
            // @codeCoverageIgnoreStart
        }
        // @codeCoverageIgnoreEnd

        return self::compareIdentifiers(
            $left->getPreRelease(),
            $right->getPreRelease()
        );
    }

    /**
     * Checks if the left version is equal to the right.
     *
     * @param Version $left  The left version to compare.
     * @param Version $right The right version to compare.
     *
     * @return boolean TRUE if the left version is equal to the right, FALSE
     *                 if not.
     */
    public static function isEqualTo(Version $left, Version $right)
    {
        return (self::EQUAL_TO === self::compareTo($left, $right));
    }

    /**
     * Checks if the left version is greater than the right.
     *
     * @param Version $left  The left version to compare.
     * @param Version $right The right version to compare.
     *
     * @return boolean TRUE if the left version is greater than the right,
     *                 FALSE if not.
     */
    public static function isGreaterThan(Version $left, Version $right)
    {
        return (self::GREATER_THAN === self::compareTo($left, $right));
    }

    /**
     * Checks if the left version is less than the right.
     *
     * @param Version $left  The left version to compare.
     * @param Version $right The right version to compare.
     *
     * @return boolean TRUE if the left version is less than the right,
     *                 FALSE if not.
     */
    public static function isLessThan(Version $left, Version $right)
    {
        return (self::LESS_THAN === self::compareTo($left, $right));
    }

    /**
     * Compares the identifier components of the left and right versions.
     *
     * @param array $left  The left identifiers.
     * @param array $right The right identifiers.
     *
     * @return integer Returns Comparator::EQUAL_TO if the two identifiers are
     *                 equal. If the left identifiers is less than the right
     *                 identifiers, Comparator::LESS_THAN is returned. If the
     *                 left identifiers is greater than the right identifiers,
     *                 Comparator::GREATER_THAN is returned.
     */
    public static function compareIdentifiers(array $left, array $right)
    {
        if ($left && empty($right)) {
            return self::LESS_THAN;
        } elseif (empty($left) && $right) {
            return self::GREATER_THAN;
        }

        $l = $left;
        $r = $right;
        $x = self::GREATER_THAN;
        $y = self::LESS_THAN;

        if (count($l) < count($r)) {
            $l = $right;
            $r = $left;
            $x = self::LESS_THAN;
            $y = self::GREATER_THAN;
        }

        foreach (array_keys($l) as $i) {
            if (!isset($r[$i])) {
                return $x;
            }

            if ($l[$i] === $r[$i]) {
                continue;
            }

            if (true === ($li = (false != preg_match('/^\d+$/', $l[$i])))) {
                $l[$i] = intval($l[$i]);
            }

            if (true === ($ri = (false != preg_match('/^\d+$/', $r[$i])))) {
                $r[$i] = intval($r[$i]);
            }

            if ($li && $ri) {
                return ($l[$i] > $r[$i]) ? $x : $y;
            } elseif (!$li && $ri) {
                return $x;
            } elseif ($li && !$ri) {
                return $y;
            }

            $result = strcmp($l[$i], $r[$i]);

            if ($result > 0) {
                return $x;
            } elseif ($result < 0) {
                return $y;
            }
        }

        return self::EQUAL_TO;
    }
}

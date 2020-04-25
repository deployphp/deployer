<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Selector;

use Deployer\Host\Host;
use Deployer\Host\HostCollection;
use function Deployer\Support\array_all;

class Selector
{
    private $hosts;

    public function __construct(HostCollection $hosts)
    {
        $this->hosts = $hosts;
    }

    /**
     * @param string $selectExpression
     * @return Host[]
     */
    public function selectHosts(string $selectExpression)
    {
        $selector = self::parse($selectExpression);

        $hosts = [];
        foreach ($this->hosts as $host) {
            if (self::apply($selector, $host)) {
                $hosts[] = $host;
            }
        }

        return $hosts;
    }

    public static function apply($selector, Host $host)
    {
        $labels = $host->get('labels', []);

        $ok = [];
        foreach ($selector as list($op, $var, $value)) {
            if ($op === 'all') {
                $ok[] = true;
                continue;
            }
            if ($var === 'host') {
                $ok[] = self::compare($op, $host->getAlias(), $value);
            } else {
                $ok[] = self::compare($op, $labels[$var] ?? null, $value);
            }
        }

        return count($ok) > 0 && array_all($ok, function ($value) {
                return $value;
            });
    }

    private static function compare(string $op, $a, $b): bool
    {
        if ($op === '=') {
            return $a === $b;
        }
        if ($op === '!=') {
            return $a !== $b;
        }
        return false;
    }

    public static function parse(string $selectExpression)
    {
        $actions = [];
        // TODO: Implement correct parser and maybe add extra features.
        $parts = explode(',', $selectExpression);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === 'all') {
                $actions[] = ['all', null, null];
                continue;
            }
            if (preg_match('/(?<var>.+?)(?<op>!?=)(?<value>.+)/', $part, $match)) {
                $actions[] = [$match['op'], trim($match['var']), trim($match['value'])];
            } else {
                throw new \InvalidArgumentException("Invalid selector \"$part\".");
            }
        }
        return $actions;
    }
}

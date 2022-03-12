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
    /**
     * @var HostCollection
     */
    private $hosts;

    public function __construct(HostCollection $hosts)
    {
        $this->hosts = $hosts;
    }

    /**
     * @return Host[]
     */
    public function select(string $selectExpression)
    {
        $conditions = self::parse($selectExpression);

        $hosts = [];
        foreach ($this->hosts as $host) {
            if (self::apply($conditions, $host)) {
                $hosts[] = $host;
            }
        }

        return $hosts;
    }

    public static function apply(?array $conditions, Host $host): bool
    {
        if (empty($conditions)) {
            return true;
        }

        $labels = $host->get('labels', []);
        $labels['alias'] = $host->getAlias();
        $labels['true'] = 'true';
        $isTrue = function ($value) {
            return $value;
        };

        foreach ($conditions as $hmm) {
            $ok = [];
            foreach ($hmm as list($op, $var, $value)) {
                $ok[] = self::compare($op, $labels[$var] ?? null, $value);
            }
            if (count($ok) > 0 && array_all($ok, $isTrue)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string|string[] $a
     */
    private static function compare(string $op, $a, ?string $b): bool
    {
        $matchFunction = function($a, ?string $b) {
            foreach ((array)$a as $item) {
                if ($item === $b) {
                    return true;
                }
            }

            return false;
        };

        if ($op === '=') {
            return $matchFunction($a, $b);
        }
        if ($op === '!=') {
            return !$matchFunction($a, $b);
        }
        return false;
    }

    public static function parse(string $expression): array
    {
        $all = [];
        foreach (explode(',', $expression) as $sub) {
            $conditions = [];
            foreach (explode('&', $sub) as $part) {
                $part = trim($part);
                if ($part === 'all') {
                    $conditions[] = ['=', 'true', 'true'];
                    continue;
                }
                if (preg_match('/(?<var>.+?)(?<op>!?=)(?<value>.+)/', $part, $match)) {
                    $conditions[] = [$match['op'], trim($match['var']), trim($match['value'])];
                } else {
                    $conditions[] = ['=', 'alias', trim($part)];
                }
            }
            $all[] = $conditions;
        }
        return $all;
    }
}

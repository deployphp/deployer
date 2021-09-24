<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Collection\Collection;

/**
 * @method Host get($name)
 * @method Host[] getIterator()
 */
class HostCollection extends Collection
{
    /**
     * Find and return the first host in the collection with the given alias.
     *
     * @throws \InvalidArgumentException if no host is found
     */
    public function findOneByAlias(string $alias): Host
    {
        $matchingHost = null;

        foreach ($this->values as $key => $host) {
            if ($host->getAlias() === $alias) {
                $matchingHost = $host;
                break;
            }
        }
        if (null === $matchingHost) {
            $this->throwNotFound($alias);
        }

        return $matchingHost;
    }

    protected function throwNotFound(string $name): void
    {
        throw new \InvalidArgumentException("Host \"$name\" not found.");
    }
}

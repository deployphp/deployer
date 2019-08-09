<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Collection;

class PersistentCollection extends Collection
{
    private $file;

    public function __construct(string $file, array $collection = [])
    {
        $this->file = $file;
        parent::__construct($collection);
    }

    public function load()
    {
        $this->values = unserialize(file_get_contents($this->file));
    }

    public function flush()
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->file, serialize($this->values));
    }
}

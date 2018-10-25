<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support\Changelog;

class Item
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var int[]
     */
    private $references = [];

    public function __toString(): string
    {
        sort($this->references, SORT_NUMERIC);

        $references = implode('', array_map(function (int $ref): string {
            return sprintf(' [#%d]', $ref);
        }, $this->references));

        return "{$this->message}$references";
    }

    /**
     * @return void
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return void
     */
    public function addReference(int $reference)
    {
        $this->references[] = $reference;
    }
}

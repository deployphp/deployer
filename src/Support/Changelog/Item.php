<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support\Changelog;

class Item
{
    /** @var string */
    private $message;

    /**
     * @var int[]
     */
    private $references = [];

    public function __toString()
    {
        sort($this->references, SORT_NUMERIC);

        $references = join('', array_map(function ($ref) {
            return " [#$ref]";
        }, $this->references));

        return "{$this->message}$references";
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getReferences(): array
    {
        return $this->references;
    }

    public function addReference(int $reference): void
    {
        $this->references[] = $reference;
    }


}

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
     * @var string[]
     */
    private $references = [];

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

    public function addReference(string $reference): void
    {
        $this->references[] = $reference;
    }


}

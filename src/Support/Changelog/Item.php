<?php declare(strict_types=1);
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

    public function __toString(): string
    {
        sort($this->references, SORT_NUMERIC);

        $references = implode('', array_map(function ($ref) {
            return " [#$ref]";
        }, $this->references));

        $message = ucfirst($this->message);
        $message = rtrim($message, '.') . '.';
        $message = preg_replace('/^Fix /', 'Fixed ', $message);
        $message = preg_replace('/^Add /', 'Added ', $message);

        return "$message$references";
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * @param int|string $reference
     */
    public function addReference($reference)
    {
        $this->references[] = $reference;
    }
}

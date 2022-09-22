<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Documentation;

class DocTask
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $desc;
    /**
     * @var string
     */
    public $comment;
    /**
     * @var array
     */
    public $group;
    /**
     * @var string
     */
    public $recipePath;
    /**
     * @var int
     */
    public $lineNumber;

    public function mdLink(): string {
        $md = php_to_md($this->recipePath);
        $anchor = anchor($this->name);
        return "[$this->name](/docs/$md#$anchor)";
    }
}

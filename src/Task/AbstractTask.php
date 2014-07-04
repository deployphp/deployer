<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\TaskInterface;

abstract class AbstractTask implements TaskInterface
{
    /**
     * @var string
     */
    protected $description;

    /**
     * Set task description
     * @param string $description
     * @return $this
     */
    public function description($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Description of task.
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
} 
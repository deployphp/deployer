<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Exception\Exception;

class GroupTask extends Task
{
    /**
     * List of tasks
     *
     * @var mixed[]
     */
    private $group;

    /**
     * @param mixed[] $group
     */
    public function __construct(string $name, array $group = [])
    {
        $this->group = $group;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException
     */
    public function run(Context $context)
    {
        throw new \RuntimeException("Can't run group task.");
    }

    /**
     * List of dependent tasks names
     *
     * @return mixed[]
     */
    public function getGroup(): array
    {
        return $this->group;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function local()
    {
        throw new Exception('Group tasks can\'t be local.');
    }
}

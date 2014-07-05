<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

class ReferenceTask implements TaskInterface
{
    /**
     * @var TaskInterface
     */
    private $reference;

    /**
     * @param TaskInterface $reference
     */
    public function __construct(TaskInterface $reference)
    {
        $this->reference = $reference;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->reference->run();
    }
}
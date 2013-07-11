<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Tester\ApplicationTester;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    public function testTask()
    {
        $tool = new Tool();

        $str = '';

        task('first', function () use (&$str) {
            $str .= 'first';
        });

        task('second', function () use (&$str) {
            $str .= 'second';
        });

        task('all', ['second', 'first']);

        $tool->getApp()->addCommands($tool->getTasks());
        $tool->getApp()->setAutoExit(false);
        $tool->getApp()->setCatchExceptions(false);
        $app = new ApplicationTester($tool->getApp());
        $app->run(array('command' => 'all'));

        $this->assertEquals('secondfirst', $str);
    }
}

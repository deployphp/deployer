<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;


class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testApplication()
    {
        $app = new Application();
        
        $this->assertTrue($app->getDefinition()->hasOption('file'));
    }
}
 
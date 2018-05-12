<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console\Output;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class VerbosityStringTest extends TestCase
{
    public function verbosity()
    {
        return [
            ['-vvv', OutputInterface::VERBOSITY_DEBUG],
            ['-vv', OutputInterface::VERBOSITY_VERY_VERBOSE],
            ['-v', OutputInterface::VERBOSITY_VERBOSE],
            ['', OutputInterface::VERBOSITY_NORMAL],
            ['-q', OutputInterface::VERBOSITY_QUIET],
        ];
    }

    /**
     * @dataProvider verbosity
     */
    public function testToString($string, $value)
    {
        $output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');
        $output->expects($this->once())
            ->method('getVerbosity')
            ->will($this->returnValue($value));

        $verbosity = new VerbosityString($output);

        $this->assertEquals($string, (string)$verbosity);
    }
}

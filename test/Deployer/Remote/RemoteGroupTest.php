<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Remote;

class RemoteGroupTest extends \PHPUnit_Framework_TestCase
{
    public function testRemoteGroupCalls()
    {
        $remote = new RemoteGroup();

        $mock = $this->getMock('Deployer\Remote\RemoteInterface');
        $mock->expects($this->exactly(3))
            ->method('cd')
            ->with('/path');

        $mock->expects($this->exactly(3))
            ->method('uploadFile')
            ->with('/from', '/to');

        $mock->expects($this->exactly(3))
            ->method('execute');

        $remote->add('1', $mock);
        $remote->add('2', $mock);
        $remote->add('2', $mock);

        $remote->cd('/path');
        $remote->uploadFile('/from', '/to');
        $remote->execute('command');
    }

    public function testRemoteMultiGroupCalls()
    {
        $remote = new RemoteGroup();

        $mock = $this->getMock('Deployer\Remote\RemoteInterface');
        $mock->expects($this->exactly(3))
            ->method('cd')
            ->with('/path');

        $mock->expects($this->exactly(2))
            ->method('uploadFile')
            ->with('/from', '/to');

        $mock->expects($this->exactly(1))
            ->method('execute');

        $remote->add('1', $mock);
        $remote->add(array('1', '2'), $mock);
        $remote->add(array('1', '2', '3'), $mock);

        $remote->group('1');
        $remote->cd('/path');

        $remote->group('2');
        $remote->uploadFile('/from', '/to');

        $remote->group('3');
        $remote->execute('command');
    }

    public function testRemoteGroupSubsection()
    {
        $remote = new RemoteGroup();

        $mock = $this->getMock('Deployer\Remote\RemoteInterface');
        $mock->expects($this->exactly(2))
            ->method('cd');

        $mock2 = $this->getMock('Deployer\Remote\RemoteInterface');
        $mock2->expects($this->exactly(1))
            ->method('cd');

        $remote->add('one', $mock);
        $remote->add('one', $mock);
        $remote->add('two', $mock2);

        $remote->group('one');
        $remote->cd('/');
        $remote->endGroup();

        $remote->group('two');
        $remote->cd('/');
        $remote->endGroup();
    }

    public function testIsGroupExist()
    {
        $remote = new RemoteGroup();

        $mock = $this->getMock('Deployer\Remote\RemoteInterface');

        $remote->add('one', $mock);

        $this->assertTrue($remote->isGroupExist('one'));
        $this->assertFalse($remote->isGroupExist('two'));
    }


    public function testIsGroupExistWithMulti()
    {
        $remote = new RemoteGroup();

        $mock = $this->getMock('Deployer\Remote\RemoteInterface');

        $remote->add(array('one', 'two'), $mock);

        $this->assertTrue($remote->isGroupExist('one'));
        $this->assertTrue($remote->isGroupExist('two'));
        $this->assertFalse($remote->isGroupExist('three'));
    }
}
 
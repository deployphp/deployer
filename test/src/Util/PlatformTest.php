<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Util;

class PlatformTest extends \PHPUnit_Framework_TestCase
{
    protected $home;

    protected $userProfile;

    public function setUp()
    {
        $this->home = getenv('HOME');
        $this->userProfile = getenv('USERPROFILE');
    }

    public function tearDown()
    {
        if ($this->home) {
            putenv("HOME={$this->home}");
        } else {
            putenv('HOME');
        }

        if ($this->userProfile) {
            putenv("USERPROFILE={$this->userProfile}");
        } else {
            putenv('USERPROFILE');
        }
    }

    public function testGetUserDirectory()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            putenv('USERPROFILE=C:\Users\test');
            $this->assertEquals((getenv('HOME') ?: getenv('USERPROFILE')), Platform::getUserDirectory());
        } else {
            putenv('HOME=/home/test');
            $this->assertEquals((getenv('HOME') ?: getenv('USERPROFILE')), Platform::getUserDirectory());
        }
    }

    public function testIsWindows()
    {
        // Compare 2 common tests for Windows to the built-in Windows test
        $this->assertEquals(('\\' === DIRECTORY_SEPARATOR), Platform::isWindows());
        $this->assertEquals(defined('PHP_WINDOWS_VERSION_MAJOR'), Platform::isWindows());
    }
}

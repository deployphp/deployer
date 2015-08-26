<?php
/* (c) Dmitry Balabka <dmitry.balabka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Helper\RecipeTester;
use Deployer\Functions;

class RemoteServerTest extends RecipeTester
{
    const IP_REG_EXP = '#^::1#';

    /**
     * @var \Deployer\Type\Result
     */
    private $result;

    protected function setUpServer()
    {
        $username = getenv('DEPLOYER_USERNAME') ?: 'deployer';
        $password = getenv('DEPLOYER_PASSWORD') ?: 'deployer_password';
        Functions\server('remote_auth_by_password', 'localhost', 22)
            ->env('deploy_path', self::$deployPath)
            ->user($username)
            ->password($password);
        Functions\server('remote_auth_by_identity_file', 'localhost', 22)
            ->env('deploy_path', self::$deployPath)
            ->user($username)
            ->identityFile();
        Functions\server('remote_auth_by_pem_file', 'localhost', 22)
            ->env('deploy_path', self::$deployPath)
            ->user($username)
            ->pemFile('~/.ssh/id_rsa.pem');
        Functions\server('remote_auth_by_agent', 'localhost', 22)
            ->env('deploy_path', self::$deployPath)
            ->user($username)
            ->forwardAgent();
    }

    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/common.php';

        Functions\task('deploy:timeout_test', function () {
            $this->result = Functions\run('sleep 11 && echo $SSH_CLIENT');
        });
        Functions\ task('deploy:ssh_test', function () {
            $this->result = Functions\run('echo $SSH_CLIENT');
        });
        Functions\ task('deploy:agent_test', function () {
            $this->result = Functions\run('ssh -T deployer@localhost \'echo $SSH_CLIENT\'');
        });
    }

    public function testAuthByPassword()
    {
        if (false !== getenv('SCRUTINIZER')) {
            $this->markTestSkipped('Test skipped on scrutinizer environment');
        }
        $this->exec('deploy:ssh_test', ['stage' => 'remote_auth_by_password']);
        $this->assertRegExp(self::IP_REG_EXP, $this->result->getOutput());
    }

    public function testAuthByIdentityFile()
    {
        if (false !== getenv('SCRUTINIZER')) {
            $this->markTestSkipped('Test skipped on scrutinizer environment');
        }
        $this->exec('deploy:ssh_test', ['stage' => 'remote_auth_by_identity_file']);
        $this->assertRegExp(self::IP_REG_EXP, $this->result->getOutput());
    }

    public function testAuthByPemFile()
    {
        if (false !== getenv('SCRUTINIZER')) {
            $this->markTestSkipped('Test skipped on scrutinizer environment');
        }
        $this->markTestIncomplete('Will be implemented later');
        $this->exec('deploy:ssh_test', ['stage' => 'remote_auth_by_pem_file']);
        $this->assertRegExp(self::IP_REG_EXP, $this->result->getOutput());
    }

    public function testAuthByAgent()
    {
        if (false !== getenv('SCRUTINIZER')) {
            $this->markTestSkipped('Test skipped on scrutinizer environment');
        }
        $this->exec('deploy:agent_test', ['stage' => 'remote_auth_by_agent']);
        $this->assertRegExp(self::IP_REG_EXP, $this->result->getOutput());
    }

    public function testTimeout()
    {
        if (false !== getenv('SCRUTINIZER')) {
            $this->markTestSkipped('Test skipped on scrutinizer environment');
        }
        $this->exec('deploy:timeout_test', ['stage' => 'remote_auth_by_agent']);
        $this->assertRegExp(self::IP_REG_EXP, $this->result->getOutput());
    }
}

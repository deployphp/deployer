<?php
/**
 * Pure-PHP ssh-agent client.
 *
 * PHP versions 4 and 5
 *
 * Here are some examples of how to use this library:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $agent = new \phpseclib\System\SSH\Agent();
 *
 *    $ssh = new \phpseclib\Net\SSH2('www.domain.tld');
 *    if (!$ssh->login('username', $agent)) {
 *        exit('Login Failed');
 *    }
 *
 *    echo $ssh->exec('pwd');
 *    echo $ssh->exec('ls -la');
 * ?>
 * </code>
 *
 * @category  System
 * @package   SSH\Agent
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2014 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 * @internal  See http://api.libssh.org/rfc/PROTOCOL.agent
 */

namespace phpseclib\System\SSH;

use phpseclib\Crypt\RSA;
use phpseclib\System\SSH\Agent\Identity;

/**
 * Pure-PHP ssh-agent client identity factory
 *
 * requestIdentities() method pumps out \phpseclib\System\SSH\Agent\Identity objects
 *
 * @package SSH\Agent
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  internal
 */
class Agent
{
    /**#@+
     * Message numbers
     *
     * @access private
     */
    // to request SSH1 keys you have to use SSH_AGENTC_REQUEST_RSA_IDENTITIES (1)
    const SSH_AGENTC_REQUEST_IDENTITIES = 11;
    // this is the SSH2 response; the SSH1 response is SSH_AGENT_RSA_IDENTITIES_ANSWER (2).
    const SSH_AGENT_IDENTITIES_ANSWER = 12;
    // the SSH1 request is SSH_AGENTC_RSA_CHALLENGE (3)
    const SSH_AGENTC_SIGN_REQUEST = 13;
    // the SSH1 response is SSH_AGENT_RSA_RESPONSE (4)
    const SSH_AGENT_SIGN_RESPONSE = 14;
    /**#@-*/

    /**
     * Unused
     */
    const SSH_AGENT_FAILURE = 5;

    /**
     * Socket Resource
     *
     * @var Resource
     * @access private
     */
    var $fsock;

    /**
     * Default Constructor
     *
     * @return \phpseclib\System\SSH\Agent
     * @access public
     */
    function __construct()
    {
        switch (true) {
            case isset($_SERVER['SSH_AUTH_SOCK']):
                $address = $_SERVER['SSH_AUTH_SOCK'];
                break;
            case isset($_ENV['SSH_AUTH_SOCK']):
                $address = $_ENV['SSH_AUTH_SOCK'];
                break;
            default:
                user_error('SSH_AUTH_SOCK not found');
                return false;
        }

        $this->fsock = fsockopen('unix://' . $address, 0, $errno, $errstr);
        if (!$this->fsock) {
            user_error("Unable to connect to ssh-agent (Error $errno: $errstr)");
        }
    }

    /**
     * Request Identities
     *
     * See "2.5.2 Requesting a list of protocol 2 keys"
     * Returns an array containing zero or more \phpseclib\System\SSH\Agent\Identity objects
     *
     * @return Array
     * @access public
     */
    function requestIdentities()
    {
        if (!$this->fsock) {
            return array();
        }

        $packet = pack('NC', 1, self::SSH_AGENTC_REQUEST_IDENTITIES);
        if (strlen($packet) != fputs($this->fsock, $packet)) {
            user_error('Connection closed while requesting identities');
        }

        $length = current(unpack('N', fread($this->fsock, 4)));
        $type = ord(fread($this->fsock, 1));
        if ($type != self::SSH_AGENT_IDENTITIES_ANSWER) {
            user_error('Unable to request identities');
        }

        $identities = array();
        $keyCount = current(unpack('N', fread($this->fsock, 4)));
        for ($i = 0; $i < $keyCount; $i++) {
            $length = current(unpack('N', fread($this->fsock, 4)));
            $key_blob = fread($this->fsock, $length);
            $length = current(unpack('N', fread($this->fsock, 4)));
            $key_comment = fread($this->fsock, $length);
            $length = current(unpack('N', substr($key_blob, 0, 4)));
            $key_type = substr($key_blob, 4, $length);
            switch ($key_type) {
                case 'ssh-rsa':
                    $key = new RSA();
                    $key->loadKey('ssh-rsa ' . base64_encode($key_blob) . ' ' . $key_comment);
                    break;
                case 'ssh-dss':
                    // not currently supported
                    break;
            }
            // resources are passed by reference by default
            if (isset($key)) {
                $identity = new Identity($this->fsock);
                $identity->setPublicKey($key);
                $identity->setPublicKeyBlob($key_blob);
                $identities[] = $identity;
                unset($key);
            }
        }

        return $identities;
    }
}

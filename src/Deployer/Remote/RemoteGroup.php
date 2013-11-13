<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Remote;

class RemoteGroup implements RemoteInterface
{
    private $remotes = array();

    private $group = null;

    public function add($group, RemoteInterface $remote)
    {
        $this->remotes[] = array($group, $remote);
    }

    public function group($group)
    {
        $this->group = $group;
    }

    public function endGroup()
    {
        $this->group = null;
    }

    public function isGroupExist($isGroup)
    {
        foreach ($this->remotes as $list) {
            list($group, $remote) = $list;
            if($group === $isGroup) {
                return true;
            }
        }

        return false;
    }

    public function cd($directory)
    {
        foreach ($this->getRemotes() as $remote) {
            $remote->cd($directory);
        }
    }

    public function execute($command)
    {
        foreach ($this->getRemotes() as $remote) {
            $remote->execute($command);
        }
    }

    public function uploadFile($from, $to)
    {
        foreach ($this->getRemotes() as $remote) {
            $remote->uploadFile($from, $to);
        }
    }

    /**
     * @return RemoteInterface[]
     */
    private function getRemotes()
    {
        if (null === $this->group) {
            $remotes = array();
            foreach ($this->remotes as $list) {
                list($group, $remote) = $list;
                $remotes[] = $remote;
            }
            return $remotes;
        } else {
            $remotes = array();
            foreach ($this->remotes as $list) {
                list($group, $remote) = $list;

                if ($group === $this->group) {
                    $remotes[] = $remote;
                }
            }
            return $remotes;
        }
    }
}
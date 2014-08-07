<?php


namespace Deployer\Local;


use Deployer\Server\ServerInterface;

class DryRunLocal implements LocalInterface
{
    public function run($command)
    {
        writeln("[local] Run command: {$command}");
    }

    /**
     * @param ServerInterface $server
     * @param $local
     * @param $remote
     * @return mixed
     */
    public function upload(ServerInterface $server, $local, $remote)
    {
        writeln("[local] Upload {$local} to {$server}:{$remote}");
    }
}
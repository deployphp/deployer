<?php


namespace Deployer\Local;


use Deployer\Server\ServerInterface;

interface LocalInterface {
    /**
     * @param $command
     * @return mixed
     */
    public function run($command);

    /**
     * @param ServerInterface $server
     * @param $local
     * @param $remote
     * @return mixed
     */
    public function upload(ServerInterface $server, $local, $remote);
} 
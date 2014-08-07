<?php


namespace Deployer\Local;


interface LocalInterface
{
    /**
     * @param $command
     * @return mixed
     */
    public function run($command);
} 
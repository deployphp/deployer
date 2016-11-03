#!/bin/bash

if [ ! -f composer.json ]; then
    echo "Please run this from the deployer repository root"
    exit 1
fi

composer install &&
    docker build -t deployer-5.6 test/docker &&
    docker run -v $(pwd):/home/deployer/deployer -i -t deployer-5.6 \
        /home/deployer/deployer/test/docker/docker-bootstrap.sh $@

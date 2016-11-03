#!/bin/bash -xe

sudo service ssh start

eval `ssh-agent`
ssh-add
ssh-keyscan -t rsa localhost > ~/.ssh/known_hosts

cd ~/deployer && vendor/phpunit/phpunit/phpunit $@

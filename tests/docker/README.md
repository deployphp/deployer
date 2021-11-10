# Deployer E2E testing environment

This directory contains an end-to-end testing environment for Deployer.

All commands mentioned in this readme, should be executed in the `docker` directory.

## Requirements

* Docker
* docker-compose

## Running tests

The E2E are started when running the `docker-compose up` command.
This will start the `server` container that has the Apache, OpenSSH & PHP 7.3 enabled.

Once the `server` is up and running, the `deployer` container will be started and alongside it
the tests will be ran.

## Adding new E2E tests

The E2E test should be a part of the `e2e` test suite. 
Each `e2e` test class should inherit from `AbstractE2ETest` class.

Note: E2E tests will only run in an environment where env variable `E2E_ENV` has been set and has a truthy value.

## Manually accessing the `deployer` container.

The container can be accessed by running:

```
docker-compose run deployer sh
```

This command will spawn a `sh` shell inside the `deployer` container.

## About containers

### `deployer` container

The `deployer` container contains:

* git
* PHP 7.3 with XDebug enabled
* rsync
* SSH client

It is possible to access the `server` container via ssh by running:

```
ssh deployer@server
```

`root`'s public key has been added to authorized keys for `deployer` user.

#### Enabling XDebug

To enable XDebug create a `docker-compose.override.yml` file with following content:

```dockerfile
services:
  deployer:
    environment:
      # See https://docs.docker.com/docker-for-mac/networking/#i-want-to-connect-from-a-container-to-a-service-on-the-host
      # See https://github.com/docker/for-linux/issues/264
      # The `remote_host` below may optionally be replaced with `remote_connect_back=1`
      XDEBUG_CONFIG: >-
        remote_enable=1
        remote_host=${XDEBUG_HOST:-host.docker.internal}
        remote_autostart=1
        remote_port=9000
        idekey=PHPSTORM
      # This should correspond to the server declared in PHPStorm `Preferences | Languages & Frameworks | PHP | Servers`
      # Then PHPStorm will use the corresponding path mappings
      PHP_IDE_CONFIG: serverName=deployer-e2e
```

Note: you may want to set the `XDEBUG_HOST` env variable to point to your IP address when running tests in Linux.

### `server` container

The `server` container contains:

* Apache (with the `DocumentRoot` set to `/var/www/html/current`)
* git
* PHP 7.3
* SSH server with
* sudo (user `deployer` can use `sudo` after providing a password: `deployer`)

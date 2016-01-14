# Running tests with docker

If you have docker installed on your local system, just run `test/docker/run.sh` from the root of the repository.

If you cannot or don't want to install docker locally, you can use [docker machine](https://docs.docker.com/machine/) do run the tests inside a VM or e.g. a Digital Ocean container. After having created a docker machine (lets say you called it `default`), just load its environment:

    eval "$(docker-machine env default)"

Now you can run `test/docker/run.sh` as if docker was installed locally.

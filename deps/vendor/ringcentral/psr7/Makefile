all: clean test

test:
	vendor/bin/phpunit $(TEST)

coverage:
	vendor/bin/phpunit --coverage-html=artifacts/coverage $(TEST)

view-coverage:
	open artifacts/coverage/index.html

clean:
	rm -rf artifacts/*

.PHONY: docker-login
docker-login:
	docker run -t -i -v $(shell pwd):/opt/psr7 ringcentral-psr7 /bin/bash

.PHONY: docker-build
docker-build:
	docker build -t ringcentral-psr7 .
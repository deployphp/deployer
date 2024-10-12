FROM php:8.3-cli-alpine

RUN apk add --no-cache bash git openssh-client rsync

WORKDIR /app

COPY deployer.phar deployer.phar

ENTRYPOINT ["php", "deployer.phar"]

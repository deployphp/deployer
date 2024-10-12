FROM php:8.2-cli-alpine

RUN apk add --no-cache bash git openssh-client rsync

COPY deployer.phar deployer.phar

WORKDIR /app

ENTRYPOINT ["php", "deployer.phar"]

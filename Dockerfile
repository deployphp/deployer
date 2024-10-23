FROM php:8.3-cli-alpine

RUN apk add --no-cache bash git openssh-client rsync

COPY deployer.phar /bin/deployer.phar

WORKDIR /app

ENTRYPOINT ["php", "/bin/deployer.phar"]

FROM php:8.4-cli-alpine

RUN apk update && apk add --no-cache linux-headers bash git openssh-client rsync zip unzip libzip-dev curl-dev

RUN docker-php-ext-install pcntl sockets curl zip

COPY --chmod=755 deployer.phar /bin/dep

WORKDIR /app

ENTRYPOINT ["/bin/dep"]

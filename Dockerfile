FROM php:8.4-cli-alpine

RUN apk add --no-cache bash git openssh-client rsync zip unzip libzip-dev \

RUN docker-php-ext-install mbstring mcrypt pcntl sockets curl zip

COPY --chmod=755 deployer.phar /bin/dep

WORKDIR /app

ENTRYPOINT ["/bin/dep"]

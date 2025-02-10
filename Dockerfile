FROM php:7.4-cli-alpine

RUN apk add --no-cache bash git openssh-client rsync

COPY --chmod=755 deployer.phar /bin/dep

WORKDIR /app

ENTRYPOINT ["/bin/dep"]

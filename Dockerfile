FROM php:8.4-cli-alpine

RUN set -eux; \
    apk add --no-cache \
        bash \
        git \
        libzip \
        openssh-client \
        rsync \
        unzip \
        zip; \
    apk add --no-cache --virtual .build-deps \
        libzip-dev \
        linux-headers; \
    docker-php-ext-install -j"$(nproc)" pcntl sockets zip; \
    apk del .build-deps

COPY --chmod=755 deployer.phar /bin/dep

WORKDIR /app

ENTRYPOINT ["/bin/dep"]

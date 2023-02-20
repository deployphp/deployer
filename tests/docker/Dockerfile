FROM php:8.0-cli-alpine AS composer
RUN apk add wget
COPY ./scripts/install-composer.sh /tmp/install-composer.sh
RUN sh /tmp/install-composer.sh





FROM php:8.0-cli-alpine AS deployer
RUN apk add \
        git \
        openssh-client \
        rsync

RUN ssh-keygen \
    -q \
    -b 2048 \
    -t rsa \
    -f ~/.ssh/id_rsa

RUN git config --global user.email "e2e@deployer.test" \
    && git config --global user.name "E2E Deployer"

ARG XDEBUG_VERSION=3.1.5
RUN set -eux; \
	apk add --no-cache --virtual .build-deps $PHPIZE_DEPS; \
	pecl install xdebug-$XDEBUG_VERSION; \
	docker-php-ext-enable xdebug; \
	apk del .build-deps

COPY scripts/php-code-coverage/coverage-start-wrapper.php /usr/local/etc/php/php-code-coverage/
COPY conf/10-coverage.ini /usr/local/etc/php/conf.d/

COPY --from=composer /tmp/composer /bin/composer
VOLUME [ "/project" ]
WORKDIR /project





FROM php:8.0-apache AS server
RUN apt-get update && apt-get install -y \
        acl \
        git \
        openssh-server \
        sudo \
    && rm -rf /var/lib/apt/lists/*

# SSH login fix. Otherwise user is kicked off after login
RUN mkdir /run/sshd \
    && sed -i 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' /etc/pam.d/sshd

# Configure Apache to expose healthcheck & configure site to use /var/www/html/current ad document root
COPY conf/healthcheck.conf /etc/apache2/sites-available/healthcheck.conf
COPY ./initial-site /var/www/html/initial-site

ENV APACHE_DOCUMENT_ROOT /var/www/html/current/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && ln -s /var/www/html/initial-site /var/www/html/current \
    && chown -R www-data:www-data /var/www/html \
    && echo "Listen 81" >> /etc/apache2/ports.conf \
    && a2enmod rewrite \
    && a2ensite healthcheck

RUN useradd \
        --create-home \
        deployer \
    && echo 'deployer:deployer' | chpasswd \
    && echo 'deployer ALL=(ALL) ALL' >> /etc/sudoers \
    && mkdir ~deployer/.ssh \
    && touch ~deployer/.ssh/authorized_keys \
    && chown -R deployer:deployer ~deployer/.ssh \
    && chmod 700 ~deployer/.ssh \
    && chmod 600 ~deployer/.ssh/authorized_keys \
    && usermod -a -G www-data deployer

RUN useradd \
        --create-home \
        git \
    && mkdir ~git/.ssh \
    && touch ~git/.ssh/authorized_keys \
    && chown -R git:git ~git/.ssh \
    && chmod 700 ~git/.ssh \
    && chmod 700 ~git/.ssh/authorized_keys \
    && mkdir ~git/repository \
    && git init --bare ~git/repository \
    && chown -R git:git ~git/repository

COPY scripts/start-servers.sh /usr/local/bin/start-servers
COPY --from=composer /tmp/composer /usr/local/bin/composer
COPY --from=deployer /root/.ssh/id_rsa.pub /tmp/root_rsa.pub

RUN chmod a+x /usr/local/bin/start-servers \
    && cat /tmp/root_rsa.pub >> ~deployer/.ssh/authorized_keys \
    && cat /tmp/root_rsa.pub >> ~git/.ssh/authorized_keys \
    && rm -rf /tmp/root_rsa.pub

EXPOSE 22 80 81
CMD [ "start-servers" ]

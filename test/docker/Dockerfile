FROM php:5.6-cli

RUN export DEBIAN_FRONTEND=noninteractive; \
    apt-get update; \
    apt-get -qq install openssh-server sudo

ENV DEPLOYER_USERNAME deployer
ENV DEPLOYER_PASSWORD deployer_password

RUN useradd --create-home --base-dir /home "${DEPLOYER_USERNAME}"
RUN echo "${DEPLOYER_USERNAME}:${DEPLOYER_PASSWORD}" | chpasswd
RUN echo "deployer ALL=NOPASSWD: ALL" >> /etc/sudoers

USER deployer
RUN mkdir -p ~/.ssh
RUN ssh-keygen -b 2048 -t rsa -f ~/.ssh/id_rsa -q -N ""
RUN cat ~/.ssh/id_rsa.pub > ~/.ssh/authorized_keys

RUN chmod 600 ~/.ssh/*

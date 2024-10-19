FROM    greensheep/dockerfiles-php-5.3
RUN     apt-get update -y
RUN     apt-get install -y curl
RUN     curl -sS https://getcomposer.org/installer | php
RUN     mv composer.phar /usr/local/bin/composer
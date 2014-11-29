if [ "$TRAVIS_PHP_VERSION" == "hhvm" ]; then
  echo "HHVM"
else 
  sudo apt-get update -qq 
  sudo apt-get install -y -qq libssh2-1-dev libssh2-php
  pecl install -f ssh2-beta < test/.noninteractive
  php -m | grep ssh2
fi;

composer self-update
composer install --no-interaction --prefer-source --dev

echo `whoami`":1234" | sudo chpasswd

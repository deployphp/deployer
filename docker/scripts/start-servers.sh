#!/bin/sh
set -e

echo "Starting SSH server"
/usr/sbin/sshd -D &

echo "Starting Apache"
exec /usr/local/bin/apache2-foreground
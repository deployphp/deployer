#!/bin/sh

set -e
set -x

export DEPLOYER_USERNAME='deployer'
export DEPLOYER_PASSWORD='deployer_password'

# Create deployer user and home directory
sudo useradd --create-home --base-dir /home "$DEPLOYER_USERNAME"

# Set deployer user password
echo "$DEPLOYER_USERNAME:$DEPLOYER_PASSWORD" | sudo chpasswd

# Create a 1024 bit RSA SSH key pair without passphrase for the travis user
ssh-keygen -t rsa -b 1024 -f "$HOME/.ssh/id_rsa" -q -N ""
ssh-keygen -f "$HOME/.ssh/id_rsa.pub" -e -m pem > "$HOME/.ssh/id_rsa.pem"

# Add the generated private key to SSH agent of travis user
ssh-add "$HOME/.ssh/id_rsa"

# Allow the private key of the travis user to log in as deployer user
sudo mkdir -p "/home/$DEPLOYER_USERNAME/.ssh/"
sudo cp "$HOME/.ssh/id_rsa.pub" "/home/$DEPLOYER_USERNAME/.ssh/authorized_keys"
sudo ssh-keyscan -t rsa localhost > "/tmp/known_hosts"
sudo cp "/tmp/known_hosts" "/home/$DEPLOYER_USERNAME/.ssh/known_hosts"
sudo chown "$DEPLOYER_USERNAME:$DEPLOYER_USERNAME" "/home/$DEPLOYER_USERNAME/.ssh/" -R

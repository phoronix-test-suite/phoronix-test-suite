#!/bin/sh

# Solus package installation

echo "Please enter your root password below:" 1>&2
sudo eopkg install --yes-all $*
exit

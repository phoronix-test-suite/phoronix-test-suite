#!/bin/sh

# Pardus package installation

echo "Please enter your root password below:" 1>&2
su root -c "pisi install --ignore-safety --yes-all $*"
exit

#!/bin/sh

# Fedora package installation

echo "Please enter your root password below:" 1>&2
su root -c "yum -y --skip-broken install $*"
exit

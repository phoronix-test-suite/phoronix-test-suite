#!/bin/sh

# Linux Embedded Development Environment package installation

echo "Please enter your root password below:" 1>&2
su root -c "/bin/opkg install $*"
exit

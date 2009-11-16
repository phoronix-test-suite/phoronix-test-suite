#!/bin/sh

# Zenwalk package installation

echo "Please enter your root password below:" 1>&2
su root -c "echo 1 | netpkg $*"
exit

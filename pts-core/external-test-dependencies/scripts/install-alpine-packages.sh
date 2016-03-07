#!/bin/sh

# Alpine Linux package installation

echo "Please enter your root password below:" 1>&2
su root -c "apk add -v $*"
exit

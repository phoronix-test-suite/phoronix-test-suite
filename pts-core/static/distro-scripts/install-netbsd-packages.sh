#!/bin/sh

# NetBSD package installation

echo "Please enter your root password below:" 1>&2
su root -c "pkg_add $*"
exit

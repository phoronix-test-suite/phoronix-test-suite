#!/bin/sh

# FreeBSD package installation

echo "Please enter your root password below:" 1>&2
su root -c "PACKAGESITE=\"ftp://ftp.freebsd.org/pub/FreeBSD/ports/i386/packages-7-stable/Latest/\" pkg_add -r $*"
exit

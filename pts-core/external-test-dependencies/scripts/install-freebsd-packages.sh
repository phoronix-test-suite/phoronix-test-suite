#!/bin/sh

# FreeBSD package installation

# for now try to set BATCH several ways...
setenv BATCH 1
# using config-reursive would be another option...

for portdir in $*
do
  cd /usr/ports/$portdir
  sudo make config-recursive install clean BATCH="yes"
done

#echo "Please enter your root password below:" 1>&2
#su root -c "PACKAGESITE=\"ftp://ftp.freebsd.org/pub/FreeBSD/ports/i386/packages-7-stable/Latest/\" pkg_add -r $*"
#exit

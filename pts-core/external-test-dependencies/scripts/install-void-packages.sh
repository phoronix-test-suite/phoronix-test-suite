#!/bin/sh
 
# Void Linux package installation

echo "Please enter your root password below:" 1>&2 
su root -c "xbps-install -Sy $*"
exit

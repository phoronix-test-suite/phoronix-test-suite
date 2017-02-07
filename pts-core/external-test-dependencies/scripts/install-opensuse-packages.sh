#!/bin/sh

# OpenSuSE package installation

echo "Please enter your root password below:" 1>&2

if [ -x /usr/bin/zypper ]; then
	su root -c "zypper install -l -y -n --force-resolution $*"
else
	su root -c "yast -i $*"
fi

exit
